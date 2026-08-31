from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .db import fetch_catalog
from .nlp_llm import _category_is_corroborated, understand
from .ranking import rank_products
from .schemas import Offer, ProductResult, QueryRequest, QueryResponse
from .search import hybrid_search

app = FastAPI(title="ShopMate AI Service", version="0.1.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/health")
def health() -> dict:
    return {"status": "ok"}


@app.post("/chat/query", response_model=QueryResponse)
def chat_query(request: QueryRequest) -> QueryResponse:
    if request.intent is not None and request.entities is not None:
        # Retry after LiveSearchFallbackService ingested new listings for
        # this exact message (see ChatController::send()) - re-derive
        # distrust_category the same way understand() would, rather than
        # assuming it, since the corroboration check only needs the
        # message text and the already-resolved category, both of which
        # are available here.
        intent, entities = request.intent, request.entities
        category = entities.get("category")
        distrust_category = category is not None and not _category_is_corroborated(request.message, category)
    else:
        intent, entities, distrust_category = understand(request.message)

    catalog = fetch_catalog()
    if not request.include_international:
        catalog = _domestic_only(catalog)

    candidates = hybrid_search(catalog, request.message, entities, distrust_category=distrust_category)
    ranked = rank_products(candidates)

    results = [_to_product_result(product) for product in ranked]

    return QueryResponse(intent=intent, entities=entities, results=results)


def _domestic_only(catalog: list[dict]) -> list[dict]:
    """Drops international-store listings, then any product left with no
    listings at all - a product that only exists at an international store
    should disappear entirely when the toggle is off, not show up with an
    empty offers list.
    """
    filtered = []
    for product in catalog:
        listings = [l for l in product["listings"] if l["store_origin"] == "domestic"]
        if listings:
            filtered.append({**product, "listings": listings})

    return filtered


def _to_offer(listing: dict) -> Offer:
    return Offer(
        id=listing["id"],
        store_name=listing["store_name"],
        store_title=listing["store_title"],
        price=listing["price"],
        delivery_charge=listing["delivery_charge"],
        total_cost=listing["price"] + listing["delivery_charge"],
        rating=listing["rating"],
        review_count=listing["review_count"],
        in_stock=listing["in_stock"],
        product_url=listing["product_url"],
        image_url=listing["image_url"],
        store_origin=listing["store_origin"],
    )


def _to_product_result(product: dict) -> ProductResult:
    return ProductResult(
        id=product["id"],
        canonical_title=product["canonical_title"],
        category=product["category"],
        brand=product["brand"],
        score=product["score"],
        reason=product["reason"],
        best_offer=_to_offer(product["best_offer"]),
        all_offers=[_to_offer(listing) for listing in product["listings"]],
    )
