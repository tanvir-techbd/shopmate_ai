from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

# Below this raw TF-IDF cosine similarity, a match with no other grounding
# is treated as coincidental word overlap rather than a real hit - see
# hybrid_search()'s docstring for how this was calibrated and where it
# applies.
MIN_RELEVANCE_FLOOR = 0.20

STRUCTURED_ENTITY_KEYS = (
    "category", "brand", "colour", "budget_max", "budget_min", "rating_min", "free_delivery",
)

# Roughly the end of Latin Extended-B - past this, a letter is Bangla,
# Cyrillic, CJK, etc. The catalogue's titles/descriptions are English-only,
# so a query with non-Latin letters (e.g. "কালো ব্যাগ") can never show TF-IDF
# overlap with a correct match, regardless of script - that is a property
# of the query's language, not evidence the match is wrong. See
# hybrid_search()'s docstring point 3.
_LATIN_LETTER_MAX_CODEPOINT = 0x024F


def _has_non_latin_letters(text: str) -> bool:
    return any(ch.isalpha() and ord(ch) > _LATIN_LETTER_MAX_CODEPOINT for ch in text)


def _product_text(product: dict) -> str:
    parts = [
        product.get("canonical_title") or "",
        product.get("category") or "",
        product.get("brand") or "",
        product.get("description") or "",
    ]
    return " ".join(parts)


def hybrid_search(catalog: list[dict], query: str, entities: dict, distrust_category: bool = False) -> list[dict]:
    """Keyword-filtered + TF-IDF relevance hybrid retrieval.

    TF-IDF stands in for a full sentence-embedding model here so the AI
    service has no torch/transformers dependency - it stays light enough to
    run on a low-spec laptop. See docs/IMPLEMENTATION_PLAN.md for the
    documented upgrade path to sentence-transformers once needed.

    Two failure modes used to make this return products with no real
    bearing on the query, instead of admitting nothing matched - both
    guarded by the same per-candidate relevance floor (MIN_RELEVANCE_FLOOR),
    just triggered under different conditions:

    1. When *no* structured entity was extracted at all (a fully free-text
       query), every product is a "candidate" by default, so an
       unconstrained TF-IDF pass over the whole catalogue can surface
       coincidental overlap as if it were a match. Calibrating against this
       catalogue: genuine unconstrained title matches (e.g. "Redmi Note 13"
       -> "Xiaomi Redmi Note 13") score >= 0.29, while coincidental overlap
       (e.g. "gaming console" -> a computer desk, "car tires" -> a vacuum
       cleaner) tops out at 0.19. MIN_RELEVANCE_FLOOR sits in that gap.
    2. A structured category filter (category/brand/budget/etc.) is
       trusted unconditionally once non-empty when entities came from the
       rule-based pipeline, since a category there is only ever set from a
       literal keyword actually present in the message. That guarantee
       doesn't hold once nlp_llm.py can supply a category too: an LLM can
       place "washing machine" in "Home Appliances" on loose semantic
       association even though this catalogue has no washing machines at
       all. distrust_category (set by main.py only when nlp_llm.py's
       category wasn't corroborated by a literal CATEGORY_SYNONYMS keyword
       - see nlp_llm.py's understand()) applies the same floor there too.

       An earlier version of this rule rejected the *whole* candidate set
       when every one of them showed zero relevance, rather than filtering
       candidates individually - which meant one coincidentally-matching
       product let the entire wrong set through uncut: "washing machine"
       (nothing genuine in the catalogue) still returned 27 unrelated
       Home Appliances once the catalogue grew a "Multi Purpose Mixer
       Grinder Blender **Machine**" that shared the word "machine". A
       per-candidate floor keeps that one weak coincidental match out too,
       same as the unconstrained case already did.

       Not applied to a non-Latin query (e.g. "কালো ব্যাগ" for an
       uncorroborated category, which is rare but possible): the
       English-only catalogue can never show TF-IDF overlap with Bangla
       regardless of whether the match is right, so the floor would reject
       everything on a property of the query's language, not the match's
       correctness.
    """
    if not catalog:
        return []

    has_structured_filter = any(entities.get(key) for key in STRUCTURED_ENTITY_KEYS)
    candidates = _apply_filters(catalog, entities)

    if has_structured_filter and not candidates:
        return []

    corpus = [_product_text(p) for p in candidates]
    vectorizer = TfidfVectorizer(stop_words="english")
    try:
        matrix = vectorizer.fit_transform(corpus + [query])
    except ValueError:
        # query/catalog had no tokens the vectorizer recognised - nothing to
        # rank on, so there is no basis for a match either way.
        return []

    similarities = cosine_similarity(matrix[-1], matrix[:-1]).flatten()

    for product, score in zip(candidates, similarities):
        product["relevance"] = float(score)

    trusted_category = has_structured_filter and not distrust_category
    if not trusted_category and not _has_non_latin_letters(query):
        candidates = [p for p in candidates if p["relevance"] >= MIN_RELEVANCE_FLOOR]

    return candidates


def _apply_filters(catalog: list[dict], entities: dict) -> list[dict]:
    results = []
    for product in catalog:
        if entities.get("category") and product.get("category") != entities["category"]:
            continue
        if entities.get("brand") and product.get("brand") != entities["brand"]:
            continue

        colour = entities.get("colour")
        if colour:
            attr_colour = (product.get("attributes") or {}).get("colour", "")
            if colour not in attr_colour.lower():
                continue

        listings = product.get("listings") or []
        if entities.get("budget_max") is not None:
            listings = [l for l in listings if (l["price"] + l["delivery_charge"]) <= entities["budget_max"]]
        if entities.get("budget_min") is not None:
            listings = [l for l in listings if (l["price"] + l["delivery_charge"]) >= entities["budget_min"]]
        if entities.get("rating_min") is not None:
            listings = [l for l in listings if (l["rating"] or 0) >= entities["rating_min"]]
        if entities.get("free_delivery"):
            listings = [l for l in listings if l["delivery_charge"] == 0]

        if not listings:
            continue

        product = {**product, "listings": listings}
        results.append(product)

    return results
