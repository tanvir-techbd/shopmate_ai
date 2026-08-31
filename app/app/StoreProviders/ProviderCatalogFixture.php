<?php

namespace App\StoreProviders;

/**
 * Deliberately independent per-store raw listing data, standing in for what
 * three real store feeds would each hand you separately - see
 * docs/ENRICHMENT_ROADMAP.md Phase B. Unlike ProductCatalogSeeder (which
 * hand-groups listings into canonical products), nothing here says which
 * listings are "the same product" - that's exactly what
 * ProductMatchingService has to work out from title/brand/category alone,
 * which is why the same conceptual product is worded differently at each
 * store, and several same-category-different-brand "distractor" products
 * are included to check the matcher doesn't over-merge.
 *
 * 'globaldeals-intl' is a fourth, fictional store representing a
 * cross-border/import aggregator rather than a domestic Bangladeshi one -
 * see StoreProviderInterface::origin(). Its listings carry a longer,
 * non-zero delivery charge and note the shipping lead time, since that's
 * the one genuinely different thing about an import purchase in a
 * catalogue with no multi-currency support.
 */
class ProviderCatalogFixture
{
    public static function forStore(string $slug): array
    {
        return self::all()[$slug] ?? [];
    }

    private static function all(): array
    {
        return [
            'trendymart-bd' => [
                ['title' => 'UrbanTrail Waterproof Laptop Backpack 15.6 Inch Black', 'price' => 2700, 'delivery_charge' => 60, 'rating' => 4.3, 'review_count' => 300, 'in_stock' => true, 'brand' => 'UrbanTrail', 'category' => 'Bags', 'description' => 'Waterproof backpack with padded 15.6-inch laptop compartment.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Fenix Slim Laptop Backpack 20L - Black', 'price' => 1450, 'delivery_charge' => 60, 'rating' => 3.9, 'review_count' => 210, 'in_stock' => true, 'brand' => 'Fenix', 'category' => 'Bags', 'description' => 'Slim commuter backpack with laptop sleeve.', 'attributes' => ['colour' => 'black']],
                ['title' => 'ToonPack Kids School Bag - Multicolour', 'price' => 890, 'delivery_charge' => 60, 'rating' => 4.0, 'review_count' => 132, 'in_stock' => true, 'brand' => 'ToonPack', 'category' => 'Bags', 'description' => 'Lightweight school backpack for kids with cartoon print.', 'attributes' => ['colour' => 'multicolour']],
                ['title' => 'Redmi Note 13 8/128GB Midnight Black', 'price' => 24999, 'delivery_charge' => 0, 'rating' => 4.6, 'review_count' => 921, 'in_stock' => true, 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'description' => '6.67" AMOLED display, 108MP camera, 5000mAh battery.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Samsung Galaxy A15 4/128GB', 'price' => 20999, 'delivery_charge' => 0, 'rating' => 4.3, 'review_count' => 356, 'in_stock' => true, 'brand' => 'Samsung', 'category' => 'Smartphones', 'description' => '6.5" sAMOLED display, 50MP triple camera.', 'attributes' => ['colour' => 'blue black']],
                ['title' => 'QCY Wireless Earbuds with ANC - Black', 'price' => 1990, 'delivery_charge' => 60, 'rating' => 4.2, 'review_count' => 540, 'in_stock' => true, 'brand' => 'QCY', 'category' => 'Audio', 'description' => 'True wireless earbuds with active noise cancellation.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Fossil Analog Steel Watch - Silver', 'price' => 5990, 'delivery_charge' => 0, 'rating' => 4.6, 'review_count' => 64, 'in_stock' => true, 'brand' => 'Fossil', 'category' => 'Watches', 'description' => 'Classic analog wrist watch, stainless steel strap.', 'attributes' => ['colour' => 'silver']],
                ['title' => 'Xiaomi Smart Band 8 - Black', 'price' => 3490, 'delivery_charge' => 0, 'rating' => 4.6, 'review_count' => 810, 'in_stock' => true, 'brand' => 'Xiaomi', 'category' => 'Wearables', 'description' => 'Fitness band with heart rate monitor, 14-day battery.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Miyako Electric Kettle 1.7L Steel', 'price' => 1250, 'delivery_charge' => 80, 'rating' => 4.3, 'review_count' => 412, 'in_stock' => true, 'brand' => 'Miyako', 'category' => 'Home Appliances', 'description' => 'Stainless steel electric kettle, auto shut-off.', 'attributes' => []],
                ['title' => 'Vector X Running Shoes for Men - Grey', 'price' => 2200, 'delivery_charge' => 60, 'rating' => 4.1, 'review_count' => 176, 'in_stock' => true, 'brand' => 'Vector X', 'category' => 'Footwear', 'description' => 'Lightweight mesh running shoes with cushioned sole.', 'attributes' => ['colour' => 'grey']],
                ['title' => 'Bata Formal Leather Shoes - Black', 'price' => 3200, 'delivery_charge' => 0, 'rating' => 4.4, 'review_count' => 88, 'in_stock' => true, 'brand' => 'Bata', 'category' => 'Footwear', 'description' => 'Genuine leather formal shoes for office wear.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Yellow Polo T-Shirt - Navy', 'price' => 950, 'delivery_charge' => 60, 'rating' => 4.2, 'review_count' => 240, 'in_stock' => true, 'brand' => 'Yellow', 'category' => 'Fashion', 'description' => 'Cotton pique polo shirt, regular fit.', 'attributes' => ['colour' => 'navy']],
                ['title' => 'ASUS Vivobook 15 Celeron 4/256GB', 'price' => 38500, 'delivery_charge' => 0, 'rating' => 4.3, 'review_count' => 152, 'in_stock' => true, 'brand' => 'ASUS', 'category' => 'Laptops', 'description' => '15.6" FHD display, everyday-use budget laptop.', 'attributes' => []],
                ['title' => 'Lenovo IdeaPad Slim 3 Ryzen 5 8/512GB', 'price' => 54900, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 97, 'in_stock' => true, 'brand' => 'Lenovo', 'category' => 'Laptops', 'description' => 'Mid-range laptop for multitasking and light gaming.', 'attributes' => []],
                ['title' => 'RainGuard Auto-Open Umbrella - Black', 'price' => 650, 'delivery_charge' => 60, 'rating' => 4.0, 'review_count' => 310, 'in_stock' => true, 'brand' => 'RainGuard', 'category' => 'Accessories', 'description' => 'Windproof auto-open umbrella, 8-rib frame.', 'attributes' => ['colour' => 'black']],
            ],
            'quickbazaar' => [
                ['title' => 'Black 15.6" Anti-Theft Laptop Bag by UrbanTrail', 'price' => 2850, 'delivery_charge' => 0, 'rating' => 4.2, 'review_count' => 140, 'in_stock' => true, 'brand' => 'UrbanTrail', 'category' => 'Bags', 'description' => 'Anti-theft laptop bag, water resistant fabric.', 'attributes' => ['colour' => 'black']],
                ['title' => 'LeatherCraft Formal Office Bag - Brown', 'price' => 3450, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 59, 'in_stock' => true, 'brand' => 'LeatherCraft', 'category' => 'Bags', 'description' => 'Genuine leather office bag with laptop compartment.', 'attributes' => ['colour' => 'brown']],
                ['title' => 'Xiaomi Redmi Note 13 (8+128GB) Black', 'price' => 25499, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 470, 'in_stock' => true, 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'description' => 'AMOLED display, 108MP camera, 5000mAh battery.', 'attributes' => ['colour' => 'black']],
                ['title' => 'QCY True Wireless ANC Earbuds (Black)', 'price' => 1850, 'delivery_charge' => 60, 'rating' => 4.1, 'review_count' => 289, 'in_stock' => true, 'brand' => 'QCY', 'category' => 'Audio', 'description' => '24-hour battery with case, IPX4 water resistance.', 'attributes' => ['colour' => 'black']],
                ['title' => 'SoundMax Bluetooth Earbuds - Black', 'price' => 1200, 'delivery_charge' => 60, 'rating' => 3.8, 'review_count' => 95, 'in_stock' => true, 'brand' => 'SoundMax', 'category' => 'Audio', 'description' => 'Budget bluetooth earbuds with charging case.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Fossil Stainless Steel Analog Watch', 'price' => 6250, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 41, 'in_stock' => false, 'brand' => 'Fossil', 'category' => 'Watches', 'description' => 'Water resistant up to 30m.', 'attributes' => ['colour' => 'silver']],
                ['title' => 'Mi Smart Band 8 Black', 'price' => 3390, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 402, 'in_stock' => true, 'brand' => 'Xiaomi', 'category' => 'Wearables', 'description' => 'Sleep tracking, AMOLED display.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Miyako 1.7 Litre Auto Cut Kettle', 'price' => 1190, 'delivery_charge' => 80, 'rating' => 4.2, 'review_count' => 198, 'in_stock' => true, 'brand' => 'Miyako', 'category' => 'Home Appliances', 'description' => 'Auto shut-off, 1500W.', 'attributes' => []],
                ['title' => 'Grey Running Sneakers by Vector X (Men)', 'price' => 2150, 'delivery_charge' => 0, 'rating' => 4.0, 'review_count' => 122, 'in_stock' => true, 'brand' => 'Vector X', 'category' => 'Footwear', 'description' => 'Breathable mesh sneakers for daily running.', 'attributes' => ['colour' => 'grey']],
                ['title' => 'Navy Blue Polo Shirt by Yellow', 'price' => 990, 'delivery_charge' => 0, 'rating' => 4.1, 'review_count' => 158, 'in_stock' => true, 'brand' => 'Yellow', 'category' => 'Fashion', 'description' => '100% cotton polo, short sleeve.', 'attributes' => ['colour' => 'navy']],
                ['title' => 'ASUS Vivobook 15 (Celeron, 4GB/256GB SSD)', 'price' => 39200, 'delivery_charge' => 0, 'rating' => 4.2, 'review_count' => 68, 'in_stock' => true, 'brand' => 'ASUS', 'category' => 'Laptops', 'description' => 'Slim budget laptop for study and office work.', 'attributes' => []],
                ['title' => 'RainGuard Windproof Umbrella (Black, Auto Open)', 'price' => 690, 'delivery_charge' => 0, 'rating' => 3.9, 'review_count' => 205, 'in_stock' => true, 'brand' => 'RainGuard', 'category' => 'Accessories', 'description' => '8-rib windproof frame, one-button auto open.', 'attributes' => ['colour' => 'black']],
            ],
            'clickbuy-bd' => [
                ['title' => 'UrbanTrail Laptop Backpack (15.6in, Waterproof, Black)', 'price' => 2790, 'delivery_charge' => 50, 'rating' => 4.4, 'review_count' => 210, 'in_stock' => true, 'brand' => 'UrbanTrail', 'category' => 'Bags', 'description' => 'Waterproof 15.6 inch laptop backpack.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Black Slim Backpack 20L by Fenix', 'price' => 1500, 'delivery_charge' => 50, 'rating' => 4.0, 'review_count' => 60, 'in_stock' => true, 'brand' => 'Fenix', 'category' => 'Bags', 'description' => 'Slim 20 litre backpack, budget friendly.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Vision 4 Slice Bread Toaster', 'price' => 1990, 'delivery_charge' => 70, 'rating' => 4.0, 'review_count' => 88, 'in_stock' => true, 'brand' => 'Vision', 'category' => 'Home Appliances', 'description' => '4-slice pop-up toaster, adjustable browning control.', 'attributes' => []],
                ['title' => 'Xiaomi Redmi Note13 8GB RAM 128GB Storage Black', 'price' => 24799, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 180, 'in_stock' => true, 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'description' => 'Redmi Note 13 with 108MP camera.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Galaxy A15 by Samsung - 4GB/128GB', 'price' => 21200, 'delivery_charge' => 0, 'rating' => 4.2, 'review_count' => 90, 'in_stock' => true, 'brand' => 'Samsung', 'category' => 'Smartphones', 'description' => 'Samsung Galaxy A15 smartphone.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Black QCY ANC Earbuds True Wireless', 'price' => 1900, 'delivery_charge' => 50, 'rating' => 4.3, 'review_count' => 120, 'in_stock' => true, 'brand' => 'QCY', 'category' => 'Audio', 'description' => 'ANC wireless earbuds with charging case.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Black SoundMax Wireless Earbuds', 'price' => 1250, 'delivery_charge' => 50, 'rating' => 3.9, 'review_count' => 40, 'in_stock' => true, 'brand' => 'SoundMax', 'category' => 'Audio', 'description' => 'Wireless earbuds, budget friendly.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Silver Fossil Analog Wrist Watch Steel Strap', 'price' => 6100, 'delivery_charge' => 0, 'rating' => 4.4, 'review_count' => 30, 'in_stock' => true, 'brand' => 'Fossil', 'category' => 'Watches', 'description' => 'Analog wrist watch with steel strap.', 'attributes' => ['colour' => 'silver']],
                ['title' => 'Xiaomi Mi Band 8 Fitness Tracker Black', 'price' => 3450, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 75, 'in_stock' => true, 'brand' => 'Xiaomi', 'category' => 'Wearables', 'description' => 'Fitness tracker with AMOLED display.', 'attributes' => ['colour' => 'black']],
                ['title' => "Men's Vector X Sports Running Shoes - Grey", 'price' => 2100, 'delivery_charge' => 0, 'rating' => 4.2, 'review_count' => 54, 'in_stock' => true, 'brand' => 'Vector X', 'category' => 'Footwear', 'description' => 'Sports running shoes, cushioned sole.', 'attributes' => ['colour' => 'grey']],
                ['title' => 'Yellow Brand Cotton Polo - Navy Blue', 'price' => 970, 'delivery_charge' => 0, 'rating' => 4.0, 'review_count' => 33, 'in_stock' => true, 'brand' => 'Yellow', 'category' => 'Fashion', 'description' => 'Regular fit cotton polo shirt.', 'attributes' => ['colour' => 'navy']],
                ['title' => 'Black Auto-Open Umbrella - Windproof 8 Rib', 'price' => 640, 'delivery_charge' => 0, 'rating' => 4.1, 'review_count' => 71, 'in_stock' => true, 'brand' => 'RainGuard', 'category' => 'Accessories', 'description' => 'Auto-open windproof umbrella.', 'attributes' => ['colour' => 'black']],
            ],
            'globaldeals-intl' => [
                ['title' => 'Sony WH-CH520 Wireless Headphones - Black (Imported)', 'price' => 6200, 'delivery_charge' => 350, 'rating' => 4.6, 'review_count' => 28, 'in_stock' => true, 'brand' => 'Sony', 'category' => 'Audio', 'description' => 'Ships from abroad - estimated delivery 10-15 days. Price includes customs and shipping.', 'attributes' => ['colour' => 'black']],
                ['title' => 'Apple Watch SE (2nd Gen) GPS 40mm - Imported Unit', 'price' => 28500, 'delivery_charge' => 500, 'rating' => 4.7, 'review_count' => 15, 'in_stock' => true, 'brand' => 'Apple', 'category' => 'Wearables', 'description' => 'Ships from abroad - estimated delivery 12-18 days. International warranty only.', 'attributes' => []],
                ['title' => 'Ray-Ban Aviator Classic Sunglasses - Gold Frame (Import)', 'price' => 9800, 'delivery_charge' => 350, 'rating' => 4.8, 'review_count' => 19, 'in_stock' => true, 'brand' => 'Ray-Ban', 'category' => 'Accessories', 'description' => 'Ships from abroad - estimated delivery 10-14 days.', 'attributes' => ['colour' => 'gold']],
                ['title' => 'Casio Edifice Chronograph Watch - Steel (Imported)', 'price' => 12500, 'delivery_charge' => 450, 'rating' => 4.5, 'review_count' => 11, 'in_stock' => true, 'brand' => 'Casio', 'category' => 'Watches', 'description' => 'Ships from abroad - estimated delivery 12-16 days.', 'attributes' => ['colour' => 'silver']],
                ['title' => 'Anker Soundcore Life Q20 Bluetooth Headphones (Import)', 'price' => 5400, 'delivery_charge' => 350, 'rating' => 4.4, 'review_count' => 22, 'in_stock' => true, 'brand' => 'Anker', 'category' => 'Audio', 'description' => 'Active noise cancelling over-ear headphones. Ships from abroad - estimated delivery 10-15 days.', 'attributes' => ['colour' => 'black']],
            ],
        ];
    }
}
