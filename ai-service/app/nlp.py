import re
import unicodedata

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
    "umbrella": "Accessories", "sunglasses": "Accessories", "sunglass": "Accessories", "wallet": "Accessories",
    "noodles": "Groceries", "noodle": "Groceries", "rice": "Groceries", "cooking oil": "Groceries",
    "shampoo": "Personal Care", "toothpaste": "Personal Care", "perfume": "Personal Care",
    "toy": "Toys", "toys": "Toys",
    "saree": "Fashion", "sari": "Fashion",
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


def _is_word_char(char: str) -> bool:
    # isalnum() covers Latin/Bangla letters and digits; Bangla vowel signs
    # and the virama (e.g. the "া"/"ো" in "কালো") are combining marks
    # (Unicode category Mc/Mn), not letters, so isalnum() alone is False for
    # them - which would put a false word boundary in the middle of a
    # Bangla word. Category "M*" catches those too.
    return char.isalnum() or unicodedata.category(char).startswith("M")


def _word_in(text: str, keyword: str) -> bool:
    """Whole-word containment, not substring - plain `in` matched "blue"
    inside "bluetooth" and misfired a colour filter on every bluetooth
    query, silently narrowing results to whatever happened to be tagged
    blue. Regex \\b was tried first, but it's driven by \\w, which (per
    Python's docs) follows str.isalnum() - False for Bangla combining
    marks - so \\bকালো\\b failed to match "কালো" at all, its own trailing
    vowel sign reads as a false word boundary. This checks the actual
    neighbouring characters instead.
    """
    start = 0
    while True:
        idx = text.find(keyword, start)
        if idx == -1:
            return False
        before_ok = idx == 0 or not _is_word_char(text[idx - 1])
        end = idx + len(keyword)
        after_ok = end == len(text) or not _is_word_char(text[end])
        if before_ok and after_ok:
            return True
        start = idx + 1


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
        if _word_in(text, keyword):
            entities["category"] = category
            break

    for keyword, colour in COLOUR_WORDS.items():
        if _word_in(text, keyword):
            entities["colour"] = colour
            break

    for brand in KNOWN_BRANDS:
        if _word_in(text, brand.lower()):
            entities["brand"] = brand
            break

    rating_match = RATING_PATTERN.search(text)
    if rating_match:
        entities["rating_min"] = float(rating_match.group(1))
    elif "highly rated" in text or "best rated" in text:
        entities["rating_min"] = 4.0

    return entities
