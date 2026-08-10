from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .db import fetch_catalog
from .nlp import classify_intent, extract_entities
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
    intent = classify_intent(request.message)
    entities = extract_entities(request.message)

    catalog = fetch_catalog()
    candidates = hybrid_search(catalog, request.message, entities)
    ranked = rank_products(candidates)

    results = [_to_product_result(product) for product in ranked]

    return QueryResponse(intent=intent, entities=entities, results=results)


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
