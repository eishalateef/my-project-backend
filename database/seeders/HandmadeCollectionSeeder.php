<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HandmadeCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'khaam-poshak')->first() ?? Store::where('is_active', true)->first();
        if (!$store) {
            $this->command->error("Khaam Poshak store not found!");
            return;
        }

        echo "Seeding Handmade Collection for Store: {$store->name} (ID: {$store->id})...\n";

        // 1. Ensure Main Categories (Men, Women, Kids)
        $men = Category::firstOrCreate(
            ['store_id' => $store->id, 'slug' => 'men'],
            [
                'name' => 'Men',
                'parent_id' => null,
                'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=900&auto=format&fit=crop&q=85',
            ]
        );

        $women = Category::firstOrCreate(
            ['store_id' => $store->id, 'slug' => 'women'],
            [
                'name' => 'Women',
                'parent_id' => null,
                'image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=900&auto=format&fit=crop&q=85',
            ]
        );

        $kids = Category::firstOrCreate(
            ['store_id' => $store->id, 'slug' => 'kids'],
            [
                'name' => 'Kids',
                'parent_id' => null,
                'image' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=900&auto=format&fit=crop&q=85',
            ]
        );

        // 2. Create the Dedicated "Handmade Collection" Category under Each
        $menHandmade = Category::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'men-handmade'],
            [
                'name' => 'Handmade Collection',
                'parent_id' => $men->id,
                'image' => 'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=900&auto=format&fit=crop&q=85',
            ]
        );

        $womenHandmade = Category::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'women-handmade'],
            [
                'name' => 'Handmade Collection',
                'parent_id' => $women->id,
                'image' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=900&auto=format&fit=crop&q=85',
            ]
        );

        $kidsHandmade = Category::updateOrCreate(
            ['store_id' => $store->id, 'slug' => 'kids-handmade'],
            [
                'name' => 'Handmade Collection',
                'parent_id' => $kids->id,
                'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
            ]
        );

        // 3. Create Subcategories for Men, Women, Kids
        $subcats = [
            // Men Subcategories
            [
                'parent_id' => $menHandmade->id,
                'name' => 'Clothing & Wearables',
                'slug' => 'men-clothing-wearables',
                'image' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $menHandmade->id,
                'name' => 'Bags & Accessories',
                'slug' => 'men-bags-accessories',
                'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $menHandmade->id,
                'name' => 'Footwear',
                'slug' => 'men-footwear',
                'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $menHandmade->id,
                'name' => 'Gifts',
                'slug' => 'men-gifts',
                'image' => 'https://images.unsplash.com/photo-1513885535751-8b9238bd345a?w=900&auto=format&fit=crop&q=85',
            ],

            // Women Subcategories
            [
                'parent_id' => $womenHandmade->id,
                'name' => 'Clothing & Textiles',
                'slug' => 'women-clothing-textiles',
                'image' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $womenHandmade->id,
                'name' => 'Bags & Purses',
                'slug' => 'women-bags-purses',
                'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $womenHandmade->id,
                'name' => 'Jewellery',
                'slug' => 'women-jewellery-handmade',
                'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $womenHandmade->id,
                'name' => 'Hair Accessories',
                'slug' => 'women-hair-accessories',
                'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $womenHandmade->id,
                'name' => 'Footwear',
                'slug' => 'women-footwear-handmade',
                'image' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $womenHandmade->id,
                'name' => 'Gifts & Décor',
                'slug' => 'women-gifts-decor',
                'image' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=900&auto=format&fit=crop&q=85',
            ],

            // Kids Subcategories
            [
                'parent_id' => $kidsHandmade->id,
                'name' => 'Clothing',
                'slug' => 'kids-clothing-handmade',
                'image' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $kidsHandmade->id,
                'name' => 'Baby Essentials',
                'slug' => 'kids-baby-essentials',
                'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $kidsHandmade->id,
                'name' => 'Toys',
                'slug' => 'kids-toys',
                'image' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $kidsHandmade->id,
                'name' => 'Accessories',
                'slug' => 'kids-accessories-handmade',
                'image' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $kidsHandmade->id,
                'name' => 'Footwear',
                'slug' => 'kids-footwear-handmade',
                'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
            ],
            [
                'parent_id' => $kidsHandmade->id,
                'name' => 'Gifts',
                'slug' => 'kids-gifts',
                'image' => 'https://images.unsplash.com/photo-1513885535751-8b9238bd345a?w=900&auto=format&fit=crop&q=85',
            ],
        ];

        $categoryMap = [];
        foreach ($subcats as $s) {
            $cat = Category::updateOrCreate(
                ['store_id' => $store->id, 'slug' => $s['slug']],
                [
                    'name' => $s['name'],
                    'parent_id' => $s['parent_id'],
                    'image' => $s['image'],
                ]
            );
            $categoryMap[$s['slug']] = $cat->id;
        }

        // 4. Seed Rich Authentic Handmade Products with Full Attributes
        $products = [
            // --- MEN HANDMADE PRODUCTS ---
            [
                'cat_slug' => 'men-clothing-wearables',
                'name' => 'Multani Hand-Embroidered Pure Cotton Kurta',
                'slug' => 'multani-hand-embroidered-cotton-kurta',
                'description' => 'Meticulously crafted by master artisans in Multan. Pure Egyptian cotton fabric featuring authentic hand-embroidered resham needlework along the neckline, collar, and cuffs.',
                'price' => 7490,
                'sale_price' => 5990,
                'stock' => 28,
                'sku' => 'KHM-MN-HEK-01',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=900&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Embroidered',
                    'subcategory' => 'Hand-Embroidered Kurtas',
                    'material' => '100% Pure Egyptian Giza Cotton',
                    'color' => 'Ivory Off-White',
                    'size' => 'S,M,L,XL',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-clothing-wearables',
                'name' => 'Handmade Raw Silk Jamawar Festive Waistcoat',
                'slug' => 'handmade-raw-silk-jamawar-waistcoat',
                'description' => 'Heirloom festive waistcoat tailored entirely by hand from hand-spun raw silk with rich banarsi jamawar weave. Finished with antique brass handcrafted buttons.',
                'price' => 9990,
                'sale_price' => 8490,
                'stock' => 18,
                'sku' => 'KHM-MN-HWC-02',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=900&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Tailored Raw Silk',
                    'subcategory' => 'Handmade Waistcoats',
                    'material' => 'Pure Raw Silk with Jamawar Weave',
                    'color' => 'Emerald Green',
                    'size' => '38,40,42,44',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-clothing-wearables',
                'name' => 'Handwoven Swati Pure Wool Heirloom Scarf',
                'slug' => 'handwoven-swati-pure-wool-scarf',
                'description' => 'Woven on traditional wooden pit looms in the valleys of Swat. Pure lamb wool offering natural warmth, geometric tribal borders, and hand-twisted fringe details.',
                'price' => 4890,
                'sale_price' => 3990,
                'stock' => 25,
                'sku' => 'KHM-MN-HWS-03',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1520903920243-00d872a2d1c9?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handwoven on Pit Looms',
                    'subcategory' => 'Handwoven Scarves',
                    'material' => '100% Pure Swati Lambswool',
                    'color' => 'Camel Brown',
                    'size' => 'Standard (2m x 0.7m)',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-clothing-wearables',
                'name' => 'Traditional Handcrafted Zari Embroidered Cap',
                'slug' => 'traditional-handcrafted-zari-cap',
                'description' => 'Heirloom prayer and wedding cap featuring meticulous tilla and gold zari handwork over structured velvet.',
                'price' => 2490,
                'sale_price' => 1990,
                'stock' => 40,
                'sku' => 'KHM-MN-HCP-04',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Embroidered Zari',
                    'subcategory' => 'Handmade Caps',
                    'material' => 'Micro-Velvet with Metallic Zari',
                    'color' => 'Royal Maroon',
                    'size' => 'Medium (21.5 in), Large (22.5 in)',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-bags-accessories',
                'name' => 'Full-Grain Hand-Stitched Vegetable Tanned Leather Wallet',
                'slug' => 'hand-stitched-vegetable-tanned-leather-wallet',
                'description' => '100% hand-cut and saddle-stitched by leather artisans using premium cowhide. Burnished beeswax edges and durable waxed linen thread.',
                'price' => 3890,
                'sale_price' => 3290,
                'stock' => 35,
                'sku' => 'KHM-MN-HLW-05',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1627123424574-724758594e93?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Saddle Hand-Stitched Leather',
                    'subcategory' => 'Handmade Leather Wallets',
                    'material' => 'Full-Grain Vegetable Tanned Leather',
                    'color' => 'Antique Cognac Tan',
                    'size' => 'Compact Bifold',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-bags-accessories',
                'name' => 'Hand-Carved Chinioti Sheesham Wood Cufflinks',
                'slug' => 'hand-carved-sheesham-wood-cufflinks',
                'description' => 'Artisanal cufflinks carved from seasoned Chinioti Sheesham (Rosewood) with mother-of-pearl inlay and stainless steel backing.',
                'price' => 2190,
                'sale_price' => 1790,
                'stock' => 50,
                'sku' => 'KHM-MN-WCL-06',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Carved Woodcraft',
                    'subcategory' => 'Wooden Cufflinks',
                    'material' => 'Solid Sheesham Rosewood & MOP Inlay',
                    'color' => 'Natural Dark Walnut',
                    'size' => 'Standard 18mm',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-bags-accessories',
                'name' => 'Handcrafted Matte Black Leather Belt with Brass Buckle',
                'slug' => 'handcrafted-matte-black-leather-belt',
                'description' => 'Single-piece thick harness leather cut and hand-beveled. Features solid forged brass hardware.',
                'price' => 3490,
                'sale_price' => 2990,
                'stock' => 30,
                'sku' => 'KHM-MN-HBL-07',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handmade Leathercraft',
                    'subcategory' => 'Handmade Belts',
                    'material' => 'Bridle Leather & Solid Brass',
                    'color' => 'Matte Midnight Black',
                    'size' => '32,34,36,38,40',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-footwear',
                'name' => 'Traditional Hand-Embroidered Velvet Khussa with Dabka Work',
                'slug' => 'mens-hand-embroidered-velvet-khussa',
                'description' => 'Master shoemaker crafted Khussa using genuine leather sole and supple velvet upper embellished with golden dabka and thread embroidery.',
                'price' => 5490,
                'sale_price' => 4690,
                'stock' => 22,
                'sku' => 'KHM-MN-HKH-08',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Embroidered Shoemaking',
                    'subcategory' => 'Handmade Khussa',
                    'material' => 'Pure Leather & Embroidered Velvet',
                    'color' => 'Jet Black with Gold Work',
                    'size' => '40,41,42,43,44,45',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'men-gifts',
                'name' => 'Personalized Handmade Leather Gift Set (Wallet, Belt & Keychain)',
                'slug' => 'personalized-handmade-leather-gift-set',
                'description' => 'Presented in a wooden keepsake box lined with green velvet. Includes customized initials hand-embossed on wallet and keychain.',
                'price' => 8990,
                'sale_price' => 7490,
                'stock' => 15,
                'sku' => 'KHM-MN-HGS-09',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1513885535751-8b9238bd345a?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Bespoke Handcrafted Leather',
                    'subcategory' => 'Handmade Gift Sets',
                    'material' => 'Full-Grain Cowhide & Solid Sheesham Box',
                    'color' => 'Dark Chocolate Brown',
                    'size' => 'Luxury Gift Box Set',
                    'gender' => 'Men',
                    'is_handmade' => '1',
                ],
            ],

            // --- WOMEN HANDMADE PRODUCTS ---
            [
                'cat_slug' => 'women-clothing-textiles',
                'name' => 'Royal Zardozi & Resham Hand-Embroidered Raw Silk Dress',
                'slug' => 'royal-zardozi-hand-embroidered-raw-silk-dress',
                'description' => 'Over 140 hours of hand needlework. Pure 80g raw silk shirt and straight trousers with intricate dabka, sitara, and resham French knots.',
                'price' => 24990,
                'sale_price' => 21990,
                'stock' => 12,
                'sku' => 'KHM-WM-HED-10',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=900&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Zardozi & Dabka Hand-Embroidery',
                    'subcategory' => 'Hand-Embroidered Dresses',
                    'material' => 'Pure Raw Silk with Silk Lining',
                    'color' => 'Deep Ruby Red',
                    'size' => 'XS,S,M,L,Custom',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-clothing-textiles',
                'name' => 'Hand Block-Printed Pure Chiffon Dupatta with Gota Edging',
                'slug' => 'hand-block-printed-pure-chiffon-dupatta',
                'description' => 'Created using hand-carved wooden blocks and organic vegetable dyes in Bahawalpur. Finished with delicate hand-stitched kiran and gota lace.',
                'price' => 5490,
                'sale_price' => 4490,
                'stock' => 26,
                'sku' => 'KHM-WM-HPD-11',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Traditional Hand-Block Print',
                    'subcategory' => 'Hand-Printed Dupattas',
                    'material' => '100% Pure Crinkle Chiffon',
                    'color' => 'Mustard Gold & Indigo',
                    'size' => '2.75 Yards Standard',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-clothing-textiles',
                'name' => 'Pure Kashmiri Handwoven Pashmina Heirloom Shawl',
                'slug' => 'pure-kashmiri-handwoven-pashmina-shawl',
                'description' => 'Spun from Himalayan mountain capra hircus down on traditional charkha wheels. Feather-soft, ring-passable, with needle-embroidered Sozni borders.',
                'price' => 38000,
                'sale_price' => 34500,
                'stock' => 8,
                'sku' => 'KHM-WM-HWS-12',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Spun & Handwoven Pashmina',
                    'subcategory' => 'Handwoven Shawls',
                    'material' => 'Grade-A 100% Kashmiri Pashmina',
                    'color' => 'Natural Ivory Cream',
                    'size' => '100 x 200 cm',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-bags-purses',
                'name' => 'Artisan Handcrafted Crochet Tote Bag with Bamboo Handles',
                'slug' => 'artisan-crochet-tote-bag-bamboo-handles',
                'description' => 'Hand-crocheted from sustainable cotton cord by women artisans in rural Punjab. Spacious interior lined with hand-printed cotton fabric.',
                'price' => 4490,
                'sale_price' => 3790,
                'stock' => 20,
                'sku' => 'KHM-WM-HCB-13',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Crochet Cord Work',
                    'subcategory' => 'Crochet Bags',
                    'material' => 'Organic Cotton Cord & Natural Bamboo',
                    'color' => 'Boho Beige / Sand',
                    'size' => 'Medium Tote (35cm x 30cm)',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-bags-purses',
                'name' => 'Bridal Hand-Embroidered Velvet Potli Bag with Pearl Handle',
                'slug' => 'bridal-hand-embroidered-velvet-potli-bag',
                'description' => 'Heavy zardozi embroidery with hanging pearl and bead latkans. The perfect heirloom accessory for weddings and festive evenings.',
                'price' => 3890,
                'sale_price' => 3190,
                'stock' => 30,
                'sku' => 'KHM-WM-EPB-14',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Embroidered Zardozi',
                    'subcategory' => 'Embroidered Potli Bags',
                    'material' => 'Micro-Velvet & Real Freshwater Pearls',
                    'color' => 'Emerald Green',
                    'size' => 'Standard Potli',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-jewellery-handmade',
                'name' => 'Handcrafted 22K Gold Plated Heritage Polki Chandbali Jhumkas',
                'slug' => 'handcrafted-polki-chandbali-jhumkas',
                'description' => 'Hand-set uncut polki stones with genuine ruby drops and hand-enameled meenakari back detailing.',
                'price' => 8490,
                'sale_price' => 6990,
                'stock' => 15,
                'sku' => 'KHM-WM-HEJ-15',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Set Kundan Polki & Meenakari',
                    'subcategory' => 'Handmade Earrings',
                    'material' => 'Brass with 22K Micron Gold Plate',
                    'color' => 'Gold & Maroon Drops',
                    'size' => 'Length: 7.5cm',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-hair-accessories',
                'name' => 'Handcrafted Traditional Bridal Paranda with Golden Gota Tassels',
                'slug' => 'handcrafted-traditional-bridal-paranda',
                'description' => 'Authentic Punjabi bridal paranda braided by hand with pure silk threads, gold gota coils, and shimmering glass mirrors.',
                'price' => 2290,
                'sale_price' => 1890,
                'stock' => 45,
                'sku' => 'KHM-WM-HPA-16',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handcrafted Silk Braiding',
                    'subcategory' => 'Traditional Parandas',
                    'material' => 'Pure Silk Thread & Gota Zari',
                    'color' => 'Crimson Red & Antique Gold',
                    'size' => 'Full Length (36 inches)',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-hair-accessories',
                'name' => 'Handmade Pure Silk Hair Scrunchies (Pack of 3)',
                'slug' => 'handmade-pure-silk-hair-scrunchies-pack',
                'description' => 'Hand-sewn from leftover pure silk fabric remnants to eliminate textile waste. Gentle on hair with strong elastic core.',
                'price' => 1490,
                'sale_price' => 1190,
                'stock' => 60,
                'sku' => 'KHM-WM-HSC-17',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Sewn Silk Craft',
                    'subcategory' => 'Handmade Scrunchies',
                    'material' => '100% Pure Raw Silk',
                    'color' => 'Blush Pink, Champagne, Sage',
                    'size' => 'Pack of 3',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-footwear-handmade',
                'name' => 'Tilla & Dabka Embroidered Leather Khussa',
                'slug' => 'tilla-dabka-embroidered-leather-khussa',
                'description' => 'Hand-molded buffalo leather sole with padded foam insole for zero bite. Intricate golden tilla embroidery on royal velvet.',
                'price' => 4290,
                'sale_price' => 3590,
                'stock' => 28,
                'sku' => 'KHM-WM-HWK-18',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handmade Shoemaking & Tilla',
                    'subcategory' => 'Handmade Khussa',
                    'material' => 'Genuine Leather & Embroidered Velvet',
                    'color' => 'Champagne Gold',
                    'size' => '36,37,38,39,40,41',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'women-gifts-decor',
                'name' => 'Handcrafted Crochet Everlasting Flower Bouquet',
                'slug' => 'handcrafted-crochet-flower-bouquet',
                'description' => 'A forever bouquet hand-knitted from soft milk cotton yarn. Features crochet roses, daisies, eucalyptus, and lavender stems tied in eco kraft paper.',
                'price' => 3690,
                'sale_price' => 2990,
                'stock' => 22,
                'sku' => 'KHM-WM-CFB-19',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Crochet Yarn Art',
                    'subcategory' => 'Crochet Flower Bouquets',
                    'material' => 'Milk Cotton Yarn & Wire Stems',
                    'color' => 'Pastel Pink, Lavender & Cream',
                    'size' => 'Height: 40cm',
                    'gender' => 'Women',
                    'is_handmade' => '1',
                ],
            ],

            // --- KIDS HANDMADE PRODUCTS ---
            [
                'cat_slug' => 'kids-clothing-handmade',
                'name' => 'Hand-Embroidered Soft Cotton Kurta for Boys (Ages 2-8)',
                'slug' => 'boys-hand-embroidered-cotton-kurta',
                'description' => 'Crafted from ultra-soft breathable lawn cotton with gentle hand-stitched resham thread motifs on the neckline.',
                'price' => 3890,
                'sale_price' => 3190,
                'stock' => 30,
                'sku' => 'KHM-KD-HEK-20',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Delicate Hand-Embroidery',
                    'subcategory' => 'Hand-Embroidered Kurtas',
                    'material' => '100% Breathable Lawn Cotton',
                    'color' => 'Powder Blue',
                    'size' => '2-3Y, 4-5Y, 6-7Y, 8-9Y',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-clothing-handmade',
                'name' => 'Handmade Baby Girl Smocked Heirloom Frock',
                'slug' => 'handmade-baby-girl-smocked-frock',
                'description' => 'Features hand-smocked bodice with dainty embroidered rosebuds and mother-of-pearl buttons. Includes soft cotton lining.',
                'price' => 4490,
                'sale_price' => 3690,
                'stock' => 20,
                'sku' => 'KHM-KD-HBD-21',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Smocked Needlework',
                    'subcategory' => 'Handmade Baby Dresses',
                    'material' => 'Fine Batiste Cotton',
                    'color' => 'Soft Coral Peach',
                    'size' => '6-12M, 12-18M, 2-3Y',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-clothing-handmade',
                'name' => 'Hand-Knit Soft Crochet Baby Cardigan Sweater',
                'slug' => 'hand-knit-crochet-baby-cardigan',
                'description' => 'Knitted by hand from hypoallergenic organic bamboo wool. Super soft against delicate infant skin with natural wood buttons.',
                'price' => 3290,
                'sale_price' => 2690,
                'stock' => 25,
                'sku' => 'KHM-KD-HCS-22',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand-Crocheted Knitting',
                    'subcategory' => 'Crochet Sweaters',
                    'material' => 'Hypoallergenic Organic Bamboo Wool',
                    'color' => 'Buttercream Yellow',
                    'size' => '0-6M, 6-12M, 1-2Y',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-baby-essentials',
                'name' => 'Handmade Crochet Baby Booties with Ribbed Ankle',
                'slug' => 'handmade-crochet-baby-booties',
                'description' => 'Keeps tiny toes cozy. Hand-knit from ultra-soft combed cotton yarn with tie-strings to prevent slipping off.',
                'price' => 1690,
                'sale_price' => 1290,
                'stock' => 40,
                'sku' => 'KHM-KD-CBB-23',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand Crochet Footwear',
                    'subcategory' => 'Crochet Baby Booties',
                    'material' => 'Combed Organic Cotton Yarn',
                    'color' => 'Oatmeal Natural',
                    'size' => '0-6 Months',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-baby-essentials',
                'name' => 'Handwoven Pure Cotton Waffle Baby Blanket',
                'slug' => 'handwoven-cotton-waffle-baby-blanket',
                'description' => 'Handwoven on shuttle looms with breathable honeycomb waffle texture. Lightweight, thermal-regulating, and machine washable.',
                'price' => 3890,
                'sale_price' => 3190,
                'stock' => 25,
                'sku' => 'KHM-KD-HBB-24',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handwoven Shuttle Loom',
                    'subcategory' => 'Handmade Baby Blankets',
                    'material' => '100% Pre-Washed Organic Cotton',
                    'color' => 'Sage Mint Green',
                    'size' => '100cm x 120cm Crib Size',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-toys',
                'name' => 'Handmade Crochet Bunny Amigurumi Soft Toy',
                'slug' => 'handmade-crochet-bunny-amigurumi-toy',
                'description' => 'Lovingly hand-crocheted using 100% baby-safe cotton yarn and anti-allergy polyfill stuffing. Features embroidered eyes for complete safety.',
                'price' => 2490,
                'sale_price' => 1990,
                'stock' => 35,
                'sku' => 'KHM-KD-CST-25',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Hand Crochet Amigurumi',
                    'subcategory' => 'Crochet Soft Toys',
                    'material' => 'Pure Cotton Yarn & Polyfill',
                    'color' => 'Soft Grey & Pink Ears',
                    'size' => 'Height: 28cm',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-accessories-handmade',
                'name' => 'Handmade Floral Bow & Ribbon Kids Hairband Set',
                'slug' => 'handmade-floral-ribbon-kids-hairband-set',
                'description' => 'Comfortable satin-wrapped flexible band with hand-sewn organza flowers and chiffon rosettes that won\'t pinch gentle heads.',
                'price' => 1490,
                'sale_price' => 1190,
                'stock' => 50,
                'sku' => 'KHM-KD-HKH-26',
                'is_featured' => false,
                'images' => [
                    'https://images.unsplash.com/photo-1544717305-2782549b5136?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handcrafted Ribbon Craft',
                    'subcategory' => 'Handmade Hairbands',
                    'material' => 'Satin Band & Silk Ribbon Rosettes',
                    'color' => 'Blush & Gold',
                    'size' => 'One Size (Ages 1-10)',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-footwear-handmade',
                'name' => 'Little Princess Hand-Embroidered Velvet Khussa',
                'slug' => 'little-princess-hand-embroidered-khussa',
                'description' => 'Featherlight genuine leather khussa with cushioned sole and gentle elastic back to stay securely on active little feet.',
                'price' => 2890,
                'sale_price' => 2290,
                'stock' => 30,
                'sku' => 'KHM-KD-HKK-27',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handmade Traditional Shoemaking',
                    'subcategory' => 'Handmade Kids\' Khussa',
                    'material' => 'Soft Leather & Velvet Embroidery',
                    'color' => 'Golden Rose Gold',
                    'size' => '22,24,26,28,30',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
            [
                'cat_slug' => 'kids-gifts',
                'name' => 'Newborn Heirloom Handmade Gift Hamper',
                'slug' => 'newborn-heirloom-handmade-gift-hamper',
                'description' => 'Presented in a woven cane basket. Includes handmade crochet blanket, baby cardigan, crochet rattle toy, booties, and personalized greeting card.',
                'price' => 9990,
                'sale_price' => 8490,
                'stock' => 15,
                'sku' => 'KHM-KD-NGH-28',
                'is_featured' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1513885535751-8b9238bd345a?w=900&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'craftsmanship' => 'Handmade Artisan Hamper',
                    'subcategory' => 'Newborn Gift Hampers',
                    'material' => '100% Organic Cotton, Wool & Cane Basket',
                    'color' => 'Natural Neutral Cream',
                    'size' => 'Complete Gift Hamper',
                    'gender' => 'Kids',
                    'is_handmade' => '1',
                ],
            ],
        ];

        foreach ($products as $pData) {
            $catId = $categoryMap[$pData['cat_slug']] ?? null;
            if (!$catId) continue;

            $product = Product::updateOrCreate(
                ['store_id' => $store->id, 'slug' => $pData['slug']],
                [
                    'category_id' => $catId,
                    'name' => $pData['name'],
                    'description' => $pData['description'],
                    'price' => $pData['price'],
                    'sale_price' => $pData['sale_price'],
                    'stock' => $pData['stock'],
                    'sku' => $pData['sku'],
                    'is_featured' => $pData['is_featured'],
                    'status' => 'active',
                    'images' => $pData['images'],
                ]
            );

            // Seed Attributes
            foreach ($pData['attributes'] as $attrKey => $attrVal) {
                ProductAttribute::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'attribute_name' => $attrKey,
                    ],
                    [
                        'attribute_value' => $attrVal,
                    ]
                );
            }
        }

        echo "Handmade Collection successfully seeded with " . count($products) . " handcrafted products across Men, Women, and Kids!\n";
    }
}
