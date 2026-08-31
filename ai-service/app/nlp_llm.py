"""
LLM-backed intent/entity extraction - an optional drop-in replacement for
the rule-based pipeline in nlp.py, calling an OpenAI-compatible chat
completions endpoint (local Ollama's /v1 route, or a hosted gateway - see
config.py's LLM_* settings, sourced from ai-service/.env).

understand() always falls back to the rule-based classify_intent()/
extract_entities() pair on ANY failure - network error, timeout, non-2xx,
malformed JSON, an out-of-vocabulary field - so a flaky or misconfigured
backend degrades to the existing behaviour instead of breaking search.
Category especially is defensively re-validated against the same closed
set _apply_filters() in search.py compares against exactly: an
LLM-invented category string (e.g. "Backpacks" instead of "Bags") would
otherwise silently zero out every result rather than just failing to
narrow them.

eval/run_evaluation.py imports classify_intent/extract_entities directly,
never this module, so Phase 6's numbers stay tied to the deterministic
rule-based pipeline regardless of whether an LLM is configured.
"""

import json
import re

import httpx

from . import config
from .nlp import CATEGORY_SYNONYMS, COLOUR_WORDS, INTENT_KEYWORDS, _word_in, classify_intent, extract_entities

VALID_INTENTS = set(INTENT_KEYWORDS.keys()) | {"SEARCH_PRODUCT"}
VALID_CATEGORIES = set(CATEGORY_SYNONYMS.values())
VALID_COLOURS = set(COLOUR_WORDS.values())

SYSTEM_PROMPT = (
    "You extract structured shopping search parameters from a customer's message "
    "for an e-commerce assistant. The message may be in English, Bangla, or Banglish. "
    "Reply with ONLY a single JSON object - no markdown fences, no explanation. "
    "Only set a field when the message actually implies it; otherwise use null. Schema:\n"
    "{\n"
    f'  "intent": one of {json.dumps(sorted(VALID_INTENTS))},\n'
    f'  "category": one of {json.dumps(sorted(VALID_CATEGORIES))}, or null,\n'
    '  "brand": the specific brand name mentioned (any real brand, in normal spelling), or null,\n'
    f'  "colour": one of {json.dumps(sorted(VALID_COLOURS))}, or null,\n'
    '  "budget_max": number or null,\n'
    '  "budget_min": number or null,\n'
    '  "rating_min": number or null,\n'
    '  "free_delivery": true or false\n'
    "}"
)

TIMEOUT_SECONDS = 6.0


def understand(message: str) -> tuple[str, dict, bool]:
    """Returns (intent, entities, distrust_category) - intent/entities in
    the same shape classify_intent()/extract_entities() produce, from the
    LLM when configured and it returns something usable, otherwise the
    rule-based pipeline.

    distrust_category is True only when the category came from the LLM
    *and* isn't corroborated by our own CATEGORY_SYNONYMS - i.e. the LLM
    made a semantic leap ("washing machine" -> "Home Appliances", nothing
    in the message says so) rather than recognising a synonym
    CATEGORY_SYNONYMS already knows maps there. A synonym match is trusted
    exactly as much as the rule-based pipeline always was, even when it's
    one TF-IDF's whole-token vectorizer can't see on the product side
    ("phone" -> "Smartphones" when every product says "Smartphone", or
    "earphone" -> "Audio" when every product says "earbuds"/"headphones")
    - both cost real, correctly-categorised products zero results before
    this corroboration check existed. See search.py's hybrid_search()
    docstring point 3 for how this flag is used downstream.
    """
    if config.LLM_ENABLED:
        result = _try_llm(message)
        if result is not None:
            intent, entities = result
            category = entities.get("category")
            distrust_category = category is not None and not _category_is_corroborated(message, category)
            return intent, entities, distrust_category

    return classify_intent(message), extract_entities(message), False


def _category_is_corroborated(message: str, category: str) -> bool:
    text = message.lower()
    return any(mapped == category and _word_in(text, keyword) for keyword, mapped in CATEGORY_SYNONYMS.items())


def _try_llm(message: str) -> tuple[str, dict] | None:
    try:
        response = httpx.post(
            f"{config.LLM_API_BASE}/chat/completions",
            headers={"Authorization": f"Bearer {config.LLM_API_KEY}"},
            json={
                "model": config.LLM_MODEL,
                "messages": [
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user", "content": message},
                ],
                "max_tokens": 400,
                "temperature": 0,
                "response_format": {"type": "json_object"},
            },
            timeout=TIMEOUT_SECONDS,
        )
        response.raise_for_status()
        content = response.json()["choices"][0]["message"]["content"]
        return _parse(content)
    except Exception:
        # Network error, timeout, non-2xx, unexpected response shape, bad
        # JSON - all treated the same: fall back to the rule-based pipeline.
        return None


def _parse(content: str) -> tuple[str, dict] | None:
    content = re.sub(r"^```(?:json)?|```$", "", content.strip(), flags=re.MULTILINE).strip()

    try:
        data = json.loads(content)
    except (json.JSONDecodeError, TypeError):
        return None

    if not isinstance(data, dict):
        return None

    intent = data.get("intent")
    intent = intent if intent in VALID_INTENTS else "SEARCH_PRODUCT"

    category = data.get("category")
    category = category if category in VALID_CATEGORIES else None

    colour = data.get("colour")
    colour = colour if colour in VALID_COLOURS else None

    brand = data.get("brand")
    brand = brand.strip() if isinstance(brand, str) and brand.strip() else None

    entities = {
        "category": category,
        "brand": brand,
        "colour": colour,
        "budget_max": _as_number(data.get("budget_max")),
        "budget_min": _as_number(data.get("budget_min")),
        "rating_min": _as_number(data.get("rating_min")),
        "free_delivery": bool(data.get("free_delivery", False)),
    }

    return intent, entities


def _as_number(value) -> float | None:
    if value is None:
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None
