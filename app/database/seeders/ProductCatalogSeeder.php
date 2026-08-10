<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    /**
     * Seeds a small mock multi-store catalogue so the search/matching/ranking
     * pipeline has realistic data to work against without needing a live
     * store API or affiliate feed.
     */
    public function run(): void
    {
        $trendyMart = Store::updateOrCreate(
            ['slug' => 'trendymart-bd'],
            ['name' => 'TrendyMart BD', 'base_url' => 'https://example-trendymart.test', 'is_active' => true],
        );

        $quickBazaar = Store::updateOrCreate(
            ['slug' => 'quickbazaar'],
            ['name' => 'QuickBazaar', 'base_url' => 'https://example-quickbazaar.test', 'is_active' => true],
        );

        foreach ($this->catalog() as $entry) {
            $product = Product::updateOrCreate(
                ['canonical_title' => $entry['canonical_title']],
                [
                    'category' => $entry['category'],
                    'brand' => $entry['brand'],
                    'description' => $entry['description'],
                    'attributes' => $entry['attributes'],
                ]
            );

            foreach ($entry['listings'] as $listing) {
                $store = $listing['store'] === 'trendymart' ? $trendyMart : $quickBazaar;

                ProductPrice::updateOrCreate(
                    ['product_id' => $product->id, 'store_id' => $store->id],
                    [
                        'store_title' => $listing['store_title'],
                        'price' => $listing['price'],
                        'delivery_charge' => $listing['delivery_charge'],
                        'rating' => $listing['rating'],
                        'review_count' => $listing['review_count'],
                        'in_stock' => $listing['in_stock'],
                        'product_url' => $listing['product_url'] ?? '#',
                        'last_checked_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'canonical_title' => 'Waterproof Laptop Backpack 15.6"',
                'category' => 'Bags',
                'brand' => 'UrbanTrail',
                'description' => 'Durable waterproof backpack with a padded 15.6-inch laptop compartment and a separate clothing pocket, USB charging port.',
                'attributes' => ['colour' => 'black', 'capacity_l' => 25, 'laptop_compartment' => true],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'UrbanTrail 15.6" Waterproof Laptop Bag - Black', 'price' => 2650, 'delivery_charge' => 60, 'rating' => 4.4, 'review_count' => 312, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Black Anti-theft Backpack for Laptop 15.6 inch', 'price' => 2890, 'delivery_charge' => 0, 'rating' => 4.2, 'review_count' => 154, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Canvas Travel Backpack 30L',
                'category' => 'Bags',
                'brand' => 'Wildcraft',
                'description' => 'Large canvas travel backpack, 30 litre capacity, fits a 14-inch laptop plus clothes for weekend trips.',
                'attributes' => ['colour' => 'black', 'capacity_l' => 30, 'laptop_compartment' => true],
                'listings' => [
                    ['store' => 'quickbazaar', 'store_title' => 'Wildcraft 30L Canvas Backpack - Black', 'price' => 2950, 'delivery_charge' => 70, 'rating' => 4.5, 'review_count' => 88, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Slim Laptop Backpack 20L',
                'category' => 'Bags',
                'brand' => 'Fenix',
                'description' => 'Slim commuter backpack with laptop sleeve, budget friendly, one main compartment for clothes and books.',
                'attributes' => ['colour' => 'black', 'capacity_l' => 20, 'laptop_compartment' => true],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Fenix Slim Backpack 20L Black', 'price' => 1450, 'delivery_charge' => 60, 'rating' => 3.9, 'review_count' => 210, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Xiaomi Redmi Note 13',
                'category' => 'Smartphones',
                'brand' => 'Xiaomi',
                'description' => '6.67" AMOLED display, 108MP camera, 5000mAh battery, 8GB/128GB variant.',
                'attributes' => ['colour' => 'midnight black', 'ram_gb' => 8, 'storage_gb' => 128],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Redmi Note 13 8/128GB Midnight Black', 'price' => 24999, 'delivery_charge' => 0, 'rating' => 4.6, 'review_count' => 921, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Xiaomi Redmi Note 13 (8GB+128GB) Black', 'price' => 25499, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 470, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Samsung Galaxy A15',
                'category' => 'Smartphones',
                'brand' => 'Samsung',
                'description' => '6.5" sAMOLED display, 50MP triple camera, 5000mAh battery, 4GB/128GB variant.',
                'attributes' => ['colour' => 'blue black', 'ram_gb' => 4, 'storage_gb' => 128],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Samsung Galaxy A15 4/128GB', 'price' => 20999, 'delivery_charge' => 0, 'rating' => 4.3, 'review_count' => 356, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'ASUS VivoBook 15 Laptop (i5, 8GB, 512GB SSD)',
                'category' => 'Laptops',
                'brand' => 'ASUS',
                'description' => 'Intel Core i5 13th Gen, 8GB RAM, 512GB SSD, 15.6" FHD display, ideal for study and office work.',
                'attributes' => ['ram_gb' => 8, 'storage_gb' => 512, 'cpu' => 'Intel Core i5'],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'ASUS VivoBook 15 i5 13th Gen 8GB 512GB SSD', 'price' => 68500, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 142, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Asus Vivobook 15 Core i5 8/512GB Laptop', 'price' => 67990, 'delivery_charge' => 100, 'rating' => 4.4, 'review_count' => 96, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Lenovo IdeaPad Slim 3 (Ryzen 5, 8GB, 512GB SSD)',
                'category' => 'Laptops',
                'brand' => 'Lenovo',
                'description' => 'AMD Ryzen 5 7530U, 8GB RAM, 512GB SSD, 15.6" FHD, lightweight for students.',
                'attributes' => ['ram_gb' => 8, 'storage_gb' => 512, 'cpu' => 'AMD Ryzen 5'],
                'listings' => [
                    ['store' => 'quickbazaar', 'store_title' => 'Lenovo IdeaPad Slim 3 Ryzen 5 8/512GB', 'price' => 62990, 'delivery_charge' => 0, 'rating' => 4.3, 'review_count' => 77, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Wireless Bluetooth Earbuds ANC',
                'category' => 'Audio',
                'brand' => 'QCY',
                'description' => 'True wireless earbuds with active noise cancellation, 24-hour battery with case, IPX4 water resistance.',
                'attributes' => ['colour' => 'black', 'anc' => true],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'QCY ANC Wireless Earbuds - Black', 'price' => 1990, 'delivery_charge' => 60, 'rating' => 4.2, 'review_count' => 540, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'QCY True Wireless ANC Earbuds Black', 'price' => 1850, 'delivery_charge' => 60, 'rating' => 4.1, 'review_count' => 289, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => "Men's Running Shoes Lightweight",
                'category' => 'Footwear',
                'brand' => 'Vector X',
                'description' => 'Breathable mesh running shoes, lightweight sole, available in multiple sizes.',
                'attributes' => ['colour' => 'grey', 'sizes' => [40, 41, 42, 43, 44]],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => "Vector X Men's Lightweight Running Shoes - Grey", 'price' => 1590, 'delivery_charge' => 60, 'rating' => 4.0, 'review_count' => 175, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => "Women's Casual Sneakers",
                'category' => 'Footwear',
                'brand' => 'Bata',
                'description' => 'Comfortable everyday sneakers with cushioned insole, breathable fabric upper.',
                'attributes' => ['colour' => 'white', 'sizes' => [36, 37, 38, 39, 40]],
                'listings' => [
                    ['store' => 'quickbazaar', 'store_title' => "Bata Women's Casual Sneakers White", 'price' => 2100, 'delivery_charge' => 0, 'rating' => 4.4, 'review_count' => 203, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Stainless Steel Analog Wrist Watch',
                'category' => 'Watches',
                'brand' => 'Fossil',
                'description' => 'Classic analog wrist watch, stainless steel strap, water resistant up to 30m.',
                'attributes' => ['colour' => 'silver'],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Fossil Analog Steel Watch - Silver', 'price' => 5990, 'delivery_charge' => 0, 'rating' => 4.6, 'review_count' => 64, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Fossil Stainless Steel Analog Watch', 'price' => 6250, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 41, 'in_stock' => false],
                ],
            ],
            [
                'canonical_title' => 'Electric Kettle 1.7L',
                'category' => 'Home Appliances',
                'brand' => 'Miyako',
                'description' => 'Stainless steel electric kettle, 1.7 litre capacity, auto shut-off, 1500W.',
                'attributes' => ['capacity_l' => 1.7, 'wattage' => 1500],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Miyako Electric Kettle 1.7L Steel', 'price' => 1250, 'delivery_charge' => 80, 'rating' => 4.3, 'review_count' => 412, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Miyako 1.7 Litre Auto Cut Kettle', 'price' => 1190, 'delivery_charge' => 80, 'rating' => 4.2, 'review_count' => 198, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Blender and Juicer 3-in-1',
                'category' => 'Home Appliances',
                'brand' => 'Kiam',
                'description' => '3-in-1 blender, grinder and juicer, 500W motor, stainless steel jars.',
                'attributes' => ['wattage' => 500],
                'listings' => [
                    ['store' => 'quickbazaar', 'store_title' => 'Kiam 3-in-1 Blender Grinder Juicer 500W', 'price' => 2350, 'delivery_charge' => 80, 'rating' => 4.1, 'review_count' => 267, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => "Kids' School Backpack Cartoon Print",
                'category' => 'Bags',
                'brand' => 'ToonPack',
                'description' => 'Lightweight school backpack for kids with cartoon print, padded straps, water bottle pocket.',
                'attributes' => ['colour' => 'multicolour', 'capacity_l' => 15, 'laptop_compartment' => false],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => "ToonPack Kids School Bag - Multicolour", 'price' => 890, 'delivery_charge' => 60, 'rating' => 4.0, 'review_count' => 132, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Formal Leather Office Bag',
                'category' => 'Bags',
                'brand' => 'LeatherCraft',
                'description' => 'Genuine leather office bag with laptop compartment, formal look, single shoulder strap.',
                'attributes' => ['colour' => 'brown', 'laptop_compartment' => true],
                'listings' => [
                    ['store' => 'quickbazaar', 'store_title' => 'LeatherCraft Formal Office Bag - Brown', 'price' => 3450, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 59, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => '4-Slice Bread Toaster',
                'category' => 'Home Appliances',
                'brand' => 'Vision',
                'description' => '4-slice pop-up toaster, adjustable browning control, crumb tray.',
                'attributes' => ['slots' => 4],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Vision 4 Slice Toaster', 'price' => 1990, 'delivery_charge' => 80, 'rating' => 4.0, 'review_count' => 88, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => "Men's Cotton Polo Shirt",
                'category' => 'Fashion',
                'brand' => 'Yellow',
                'description' => '100% cotton polo shirt, regular fit, available in multiple sizes and colours.',
                'attributes' => ['colour' => 'navy', 'sizes' => ['M', 'L', 'XL']],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => "Yellow Men's Cotton Polo - Navy", 'price' => 890, 'delivery_charge' => 60, 'rating' => 4.2, 'review_count' => 305, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => "Men's Polo Shirt Cotton Navy Blue", 'price' => 850, 'delivery_charge' => 60, 'rating' => 4.0, 'review_count' => 121, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Smart Fitness Band',
                'category' => 'Wearables',
                'brand' => 'Xiaomi',
                'description' => 'Fitness band with heart rate monitor, sleep tracking, 14-day battery, AMOLED display.',
                'attributes' => ['colour' => 'black'],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'Xiaomi Smart Band 8 - Black', 'price' => 3490, 'delivery_charge' => 0, 'rating' => 4.6, 'review_count' => 810, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Mi Smart Band 8 Black', 'price' => 3390, 'delivery_charge' => 0, 'rating' => 4.5, 'review_count' => 402, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Office Study Table Lamp LED',
                'category' => 'Home Appliances',
                'brand' => 'Philips',
                'description' => 'LED study/office table lamp, adjustable brightness, USB powered.',
                'attributes' => ['colour' => 'white'],
                'listings' => [
                    ['store' => 'quickbazaar', 'store_title' => 'Philips LED Study Lamp - White', 'price' => 990, 'delivery_charge' => 60, 'rating' => 4.3, 'review_count' => 147, 'in_stock' => true],
                ],
            ],
            [
                'canonical_title' => 'Compact Umbrella Windproof',
                'category' => 'Accessories',
                'brand' => 'RainGuard',
                'description' => 'Compact windproof umbrella, auto open/close, fits in a backpack side pocket.',
                'attributes' => ['colour' => 'black'],
                'listings' => [
                    ['store' => 'trendymart', 'store_title' => 'RainGuard Windproof Compact Umbrella - Black', 'price' => 650, 'delivery_charge' => 60, 'rating' => 4.1, 'review_count' => 233, 'in_stock' => true],
                    ['store' => 'quickbazaar', 'store_title' => 'Compact Auto Umbrella Black Windproof', 'price' => 590, 'delivery_charge' => 60, 'rating' => 4.0, 'review_count' => 98, 'in_stock' => true],
                ],
            ],
        ];
    }
}
