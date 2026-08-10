"""
Phase 6 evaluation harness (see docs/IMPLEMENTATION_PLAN.md).

Compares ShopMate AI's hybrid search + ranking pipeline against a naive
keyword-only baseline (unranked substring match on product title, mimicking
the "conventional keyword-based search" the proposal's MSc research question
asks about) on a small held-out query set covering budget, brand, attribute,
Bangla/Banglish and multi-constraint queries.

Usage (from ai-service/, with the venv active and MySQL reachable):
    venv/bin/python -m eval.run_evaluation
"""

import json
import math
import re
from pathlib import Path

from app.db import fetch_catalog
from app.nlp import classify_intent, extract_entities
from app.ranking import rank_products
from app.search import hybrid_search

K = 5
QUERIES_PATH = Path(__file__).parent / "queries.json"


def load_queries() -> list[dict]:
    return json.loads(QUERIES_PATH.read_text(encoding="utf-8"))


def shopmate_results(catalog: list[dict], query: str) -> list[int]:
    entities = extract_entities(query)
    candidates = hybrid_search([dict(p, listings=list(p["listings"])) for p in catalog], query, entities)
    ranked = rank_products(candidates, limit=K)
    return [p["id"] for p in ranked]


def baseline_results(catalog: list[dict], query: str) -> list[int]:
    """Naive unranked keyword search: title contains any query word, kept in
    catalogue (id) order - no budget/colour/rating understanding, no
    relevance ranking. This is the 'baseline' referenced in the proposal's
    MSc Research Evaluation section."""
    words = [w for w in re.findall(r"[a-zA-Z]+", query.lower()) if len(w) > 2]
    if not words:
        return []

    matches = []
    for product in catalog:
        title = (product["canonical_title"] or "").lower()
        if any(word in title for word in words):
            matches.append(product["id"])

    return matches[:K]


def precision_at_k(retrieved: list[int], relevant: set[int], k: int) -> float:
    if k == 0:
        return 0.0
    return len(set(retrieved[:k]) & relevant) / k


def recall_at_k(retrieved: list[int], relevant: set[int], k: int) -> float:
    if not relevant:
        return 0.0
    return len(set(retrieved[:k]) & relevant) / len(relevant)


def ndcg_at_k(retrieved: list[int], relevant: set[int], k: int) -> float:
    dcg = sum(
        1.0 / math.log2(i + 2)
        for i, pid in enumerate(retrieved[:k])
        if pid in relevant
    )
    ideal_hits = min(len(relevant), k)
    idcg = sum(1.0 / math.log2(i + 2) for i in range(ideal_hits))
    return dcg / idcg if idcg > 0 else 0.0


def main() -> None:
    catalog = fetch_catalog()
    queries = load_queries()

    rows = []
    totals = {"shopmate": {"p": 0.0, "r": 0.0, "n": 0.0}, "baseline": {"p": 0.0, "r": 0.0, "n": 0.0}}

    for q in queries:
        relevant = set(q["relevant_product_ids"])

        sm = shopmate_results(catalog, q["query"])
        bl = baseline_results(catalog, q["query"])

        sm_metrics = (precision_at_k(sm, relevant, K), recall_at_k(sm, relevant, K), ndcg_at_k(sm, relevant, K))
        bl_metrics = (precision_at_k(bl, relevant, K), recall_at_k(bl, relevant, K), ndcg_at_k(bl, relevant, K))

        for key, metrics in (("shopmate", sm_metrics), ("baseline", bl_metrics)):
            totals[key]["p"] += metrics[0]
            totals[key]["r"] += metrics[1]
            totals[key]["n"] += metrics[2]

        rows.append({
            "id": q["id"],
            "category": q["category"],
            "query": q["query"],
            "intent": classify_intent(q["query"]),
            "shopmate_ids": sm,
            "baseline_ids": bl,
            "shopmate": sm_metrics,
            "baseline": bl_metrics,
        })

    n = len(queries)
    print(f"\nShopMate AI vs. keyword baseline — Precision@{K} / Recall@{K} / NDCG@{K}\n")
    print(f"{'query':45} {'intent':18} {'SM P/R/N':22} {'Baseline P/R/N':22}")
    print("-" * 110)
    for row in rows:
        sm = "/".join(f"{v:.2f}" for v in row["shopmate"])
        bl = "/".join(f"{v:.2f}" for v in row["baseline"])
        print(f"{row['query'][:44]:45} {row['intent']:18} {sm:22} {bl:22}")

    print("-" * 110)
    print(
        f"{'AVERAGE':45} {'':18} "
        f"{totals['shopmate']['p']/n:.2f}/{totals['shopmate']['r']/n:.2f}/{totals['shopmate']['n']/n:.2f}      "
        f"{totals['baseline']['p']/n:.2f}/{totals['baseline']['r']/n:.2f}/{totals['baseline']['n']/n:.2f}"
    )

    out_path = Path(__file__).parent / "results.json"
    out_path.write_text(json.dumps(rows, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"\nFull per-query results written to {out_path}")


if __name__ == "__main__":
    main()
