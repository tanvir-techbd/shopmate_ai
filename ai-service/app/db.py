import json

from sqlalchemy import create_engine, text

from .config import DATABASE_URL

engine = create_engine(DATABASE_URL, pool_pre_ping=True)


def fetch_catalog() -> list[dict]:
    """Read-only snapshot of products joined with their store listings.

    Recomputed on every request rather than cached/indexed anywhere -
    the MVP catalogue is small (tens to low hundreds of products), so a
    persistent vector index or search engine is unnecessary overhead.
    """
    products_sql = text(
        "SELECT id, canonical_title, category, brand, description, attributes "
        "FROM products"
    )
    prices_sql = text(
        "SELECT pp.id, pp.product_id, pp.store_id, s.name AS store_name, "
        "pp.store_title, pp.price, pp.delivery_charge, pp.rating, "
        "pp.review_count, pp.in_stock, pp.product_url "
        "FROM product_prices pp JOIN stores s ON s.id = pp.store_id "
        "WHERE s.is_active = 1"
    )

    with engine.connect() as conn:
        products = [dict(row._mapping) for row in conn.execute(products_sql)]
        prices = [dict(row._mapping) for row in conn.execute(prices_sql)]

    listings_by_product: dict[int, list[dict]] = {}
    for listing in prices:
        listing["price"] = float(listing["price"])
        listing["delivery_charge"] = float(listing["delivery_charge"])
        listing["rating"] = float(listing["rating"]) if listing["rating"] is not None else None
        listing["in_stock"] = bool(listing["in_stock"])
        listings_by_product.setdefault(listing["product_id"], []).append(listing)

    catalog = []
    for product in products:
        if product["attributes"]:
            product["attributes"] = json.loads(product["attributes"]) if isinstance(product["attributes"], str) else product["attributes"]
        else:
            product["attributes"] = {}
        product["listings"] = listings_by_product.get(product["id"], [])
        catalog.append(product)

    return catalog
