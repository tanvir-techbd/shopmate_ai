from pydantic import BaseModel


class QueryRequest(BaseModel):
    message: str


class Offer(BaseModel):
    id: int
    store_name: str
    store_title: str
    price: float
    delivery_charge: float
    total_cost: float
    rating: float | None
    review_count: int
    in_stock: bool
    product_url: str | None


class ProductResult(BaseModel):
    id: int
    canonical_title: str
    category: str | None
    brand: str | None
    score: float
    reason: str
    best_offer: Offer
    all_offers: list[Offer]


class QueryResponse(BaseModel):
    intent: str
    entities: dict
    results: list[ProductResult]
