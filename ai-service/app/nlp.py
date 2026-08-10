import re

# Rule-based intent/entity engine. No external model calls and no GPU/large
# model dependency, so it runs comfortably on a low-spec laptop. Swap in a
# proper transformer classifier later (see docs/IMPLEMENTATION_PLAN.md) once
# accuracy on Bangla/Banglish queries needs to improve beyond keyword rules.

INTENT_KEYWORDS: dict[str, list[str]] = {
    "COMPARE_PRODUCT": ["compare", "vs", "versus", "difference between", "which is better"],
    "RECOMMEND_PRODUCT": ["recommend", "suggest", "best", "which one should i", "any suggestion"],
    "PRICE_ALERT": ["notify me", "alert me", "let me know when", "price drop", "if the price"],
    "STOCK_ALERT": ["back in stock", "restock", "available again", "notify when available"],
    "SHOPPING_LIST": ["add to my list", "shopping list", "remind me to buy", "add to list"],
    "TRACK_ORDER": ["track my order", "order status", "where is my order"],
    "BUY_PRODUCT": ["buy", "purchase", "order now", "checkout", "i want to buy"],
}

CATEGORY_SYNONYMS: dict[str, str] = {
    "backpack": "Bags", "bag": "Bags", "bagpack": "Bags", "ব্যাগ": "Bags",
    "phone": "Smartphones", "mobile": "Smartphones", "smartphone": "Smartphones", "ফোন": "Smartphones",
    "laptop": "Laptops", "notebook": "Laptops", "ল্যাপটপ": "Laptops",
    "earbuds": "Audio", "earphone": "Audio", "headphone": "Audio", "headset": "Audio",
    "shoe": "Footwear", "shoes": "Footwear", "sneaker": "Footwear", "sneakers": "Footwear", "জুতা": "Footwear",
    "watch": "Watches", "wristwatch": "Watches", "ঘড়ি": "Watches",
    "kettle": "Home Appliances", "toaster": "Home Appliances", "blender": "Home Appliances",
    "juicer": "Home Appliances", "lamp": "Home Appliances",
    "shirt": "Fashion", "polo": "Fashion", "t-shirt": "Fashion", "tshirt": "Fashion",
    "band": "Wearables", "fitness band": "Wearables", "smartband": "Wearables",
    "umbrella": "Accessories",
}

COLOUR_WORDS: dict[str, str] = {
    "black": "black", "কালো": "black", "kalo": "black",
    "white": "white", "সাদা": "white",
    "red": "red", "লাল": "red",
    "blue": "blue", "নীল": "blue", "navy": "navy",
    "green": "green", "সবুজ": "green",
    "grey": "grey", "gray": "grey",
    "brown": "brown",
    "silver": "silver",
    "pink": "pink",
    "yellow": "yellow",
    "multicolour": "multicolour", "multicolor": "multicolour",
}

KNOWN_BRANDS = [
    "Xiaomi", "Samsung", "ASUS", "Lenovo", "QCY", "Vector X", "Bata", "Fossil",
    "Miyako", "Kiam", "ToonPack", "LeatherCraft", "Vision", "Yellow", "Philips",
    "RainGuard", "UrbanTrail", "Wildcraft", "Fenix",
]

BUDGET_MAX_PATTERNS = [
    r"under\s*(?:tk|bdt|৳)?\s*([\d,]+)",
    r"below\s*(?:tk|bdt|৳)?\s*([\d,]+)",
    r"less than\s*(?:tk|bdt|৳)?\s*([\d,]+)",
    r"within\s*(?:tk|bdt|৳)?\s*([\d,]+)",
    r"(?:tk|bdt|৳)\s*([\d,]+)\s*(?:er niche|er moddhe)",  # Banglish: "3000 taka-r niche"
    r"max(?:imum)?\s*(?:tk|bdt|৳)?\s*([\d,]+)",
]

BUDGET_MIN_PATTERNS = [
    r"over\s*(?:tk|bdt|৳)?\s*([\d,]+)",
    r"above\s*(?:tk|bdt|৳)?\s*([\d,]+)",
    r"more than\s*(?:tk|bdt|৳)?\s*([\d,]+)",
]

RATING_PATTERN = re.compile(r"(\d(?:\.\d)?)\s*\+?\s*star")


def classify_intent(message: str) -> str:
    text = message.lower()
    for intent, keywords in INTENT_KEYWORDS.items():
        if any(keyword in text for keyword in keywords):
            return intent
    return "SEARCH_PRODUCT"


def _extract_number(patterns: list[str], text: str) -> float | None:
    for pattern in patterns:
        match = re.search(pattern, text)
        if match:
            return float(match.group(1).replace(",", ""))
    return None


def extract_entities(message: str) -> dict:
    text = message.lower()

    entities: dict = {
        "category": None,
        "brand": None,
        "colour": None,
        "budget_max": _extract_number(BUDGET_MAX_PATTERNS, text),
        "budget_min": _extract_number(BUDGET_MIN_PATTERNS, text),
        "rating_min": None,
        "free_delivery": "free delivery" in text,
    }

    for keyword, category in CATEGORY_SYNONYMS.items():
        if keyword in text:
            entities["category"] = category
            break

    for keyword, colour in COLOUR_WORDS.items():
        if keyword in text:
            entities["colour"] = colour
            break

    for brand in KNOWN_BRANDS:
        if brand.lower() in text:
            entities["brand"] = brand
            break

    rating_match = RATING_PATTERN.search(text)
    if rating_match:
        entities["rating_min"] = float(rating_match.group(1))
    elif "highly rated" in text or "best rated" in text:
        entities["rating_min"] = 4.0

    return entities
