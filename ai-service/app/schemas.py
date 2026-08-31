from pydantic import BaseModel


class QueryRequest(BaseModel):
    message: str
    # Off by default: a request with no explicit preference (e.g. an older
    # client) gets domestic-only results, not a surprise international one.
    include_international: bool = False
    # Set by ChatController's retry after LiveSearchFallbackService ingests
    # new listings for this exact message - skips re-running understand()
    # (an LLM round trip) since the query text hasn't changed, only the
    # catalog has. Both must be present to skip; either omitted re-parses
    # normally.
    intent: str | None = None
    entities: dict | None = None


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
    image_url: str | None
    store_origin: str


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
