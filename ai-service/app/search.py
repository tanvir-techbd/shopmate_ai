from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


def _product_text(product: dict) -> str:
    parts = [
        product.get("canonical_title") or "",
        product.get("category") or "",
        product.get("brand") or "",
        product.get("description") or "",
    ]
    return " ".join(parts)


def hybrid_search(catalog: list[dict], query: str, entities: dict) -> list[dict]:
    """Keyword-filtered + TF-IDF relevance hybrid retrieval.

    TF-IDF stands in for a full sentence-embedding model here so the AI
    service has no torch/transformers dependency - it stays light enough to
    run on a low-spec laptop. See docs/IMPLEMENTATION_PLAN.md for the
    documented upgrade path to sentence-transformers once needed.
    """
    if not catalog:
        return []

    candidates = _apply_filters(catalog, entities)
    if not candidates:
        candidates = catalog  # fall back to full catalogue, ranked by relevance only

    corpus = [_product_text(p) for p in candidates]
    vectorizer = TfidfVectorizer(stop_words="english")
    try:
        matrix = vectorizer.fit_transform(corpus + [query])
    except ValueError:
        # query/catalog had no tokens the vectorizer recognised
        for product in candidates:
            product["relevance"] = 0.0
        return candidates

    similarities = cosine_similarity(matrix[-1], matrix[:-1]).flatten()

    for product, score in zip(candidates, similarities):
        product["relevance"] = float(score)

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
