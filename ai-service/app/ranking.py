RELEVANCE_WEIGHT = 0.40
PRICE_WEIGHT = 0.25
RATING_WEIGHT = 0.20
DELIVERY_WEIGHT = 0.10
AVAILABILITY_WEIGHT = 0.05


def _best_listing(product: dict) -> dict:
    in_stock = [l for l in product["listings"] if l["in_stock"]]
    pool = in_stock or product["listings"]
    return min(pool, key=lambda l: l["price"] + l["delivery_charge"])


def _normalize(values: list[float], reverse: bool = False) -> list[float]:
    if not values:
        return []
    lo, hi = min(values), max(values)
    if hi == lo:
        return [1.0 for _ in values]
    if reverse:
        return [(hi - v) / (hi - lo) for v in values]
    return [(v - lo) / (hi - lo) for v in values]


def rank_products(products: list[dict], limit: int = 10) -> list[dict]:
    if not products:
        return []

    for product in products:
        product["best_offer"] = _best_listing(product)

    total_costs = [p["best_offer"]["price"] + p["best_offer"]["delivery_charge"] for p in products]
    ratings = [p["best_offer"]["rating"] or 0 for p in products]
    deliveries = [p["best_offer"]["delivery_charge"] for p in products]
    relevances = [p.get("relevance", 0.0) for p in products]
    availabilities = [1.0 if p["best_offer"]["in_stock"] else 0.0 for p in products]

    price_scores = _normalize(total_costs, reverse=True)
    rating_scores = _normalize(ratings)
    delivery_scores = _normalize(deliveries, reverse=True)
    relevance_scores = _normalize(relevances)

    for i, product in enumerate(products):
        product["score"] = round(
            RELEVANCE_WEIGHT * relevance_scores[i]
            + PRICE_WEIGHT * price_scores[i]
            + RATING_WEIGHT * rating_scores[i]
            + DELIVERY_WEIGHT * delivery_scores[i]
            + AVAILABILITY_WEIGHT * availabilities[i],
            4,
        )

    products.sort(key=lambda p: p["score"], reverse=True)
    top = products[:limit]

    if top:
        cheapest_id = min(top, key=lambda p: p["best_offer"]["price"] + p["best_offer"]["delivery_charge"])["id"]
        best_rated_id = max(top, key=lambda p: p["best_offer"]["rating"] or 0)["id"]
        fastest_delivery_id = min(top, key=lambda p: p["best_offer"]["delivery_charge"])["id"]

        for product in top:
            if product["id"] == cheapest_id:
                product["reason"] = "Lowest price"
            elif product["id"] == best_rated_id:
                product["reason"] = "Best rated"
            elif product["id"] == fastest_delivery_id:
                product["reason"] = "Fastest / cheapest delivery"
            else:
                product["reason"] = "Best overall match"

    return top
