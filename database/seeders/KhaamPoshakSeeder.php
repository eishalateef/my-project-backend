<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KhaamPoshakSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Remove all other stores to make it strictly a single clothing store: KHAAM POSHAK
        Store::where('slug', '!=', 'khaam-poshak')->delete();

        $store = Store::updateOrCreate(
            ['slug' => 'khaam-poshak'],
            [
                'name' => 'KHAAM POSHAK',
                'store_type' => 'clothing',
                'currency' => 'PKR',
                'currency_symbol' => 'Rs. ',
                'theme_key' => 'theme-clothing',
                'tagline' => 'خام پوشاک • Traditional Pakistani Prêt & Luxury Eastern Wear',
                'description' => 'Heirloom Pakistani craft meets contemporary luxury prêt. Woven in 100% pure Egyptian Giza lawn, hand-spun raw silk, Multani chikankari, and intricate zardozi hand-embroidery.',
                'logo_url' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=400&auto=format&fit=crop&q=85',
                'banner_url' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=1600&auto=format&fit=crop&q=85',
                'is_active' => true,
            ]
        );

        // Clear existing categories and products for this store for clean hierarchical re-seed
        Product::where('store_id', $store->id)->delete();
        Category::where('store_id', $store->id)->delete();

        // ---------------------------------------------------------------------
        // 2. MAIN DEPARTMENTS (Women, Men, Kids) & SUBCATEGORIES
        // ---------------------------------------------------------------------
        $departmentDefs = [
            'women' => [
                'name' => 'Women',
                'slug' => 'women',
                'image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=900&auto=format&fit=crop&q=85',
                'subcategories' => [
                    [
                        'name' => 'Women Clothes',
                        'slug' => 'women-clothes',
                        'image' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=900&auto=format&fit=crop&q=85',
                    ],
                    [
                        'name' => 'Jewellery',
                        'slug' => 'jewellery',
                        'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=900&auto=format&fit=crop&q=85',
                    ],
                    [
                        'name' => 'Accessories',
                        'slug' => 'accessories',
                        'image' => 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=900&auto=format&fit=crop&q=85',
                    ],
                ],
            ],
            'men' => [
                'name' => 'Men',
                'slug' => 'men',
                'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=900&auto=format&fit=crop&q=85',
                'subcategories' => [
                    [
                        'name' => 'Men Eastern Wear',
                        'slug' => 'men-eastern-wear',
                        'image' => 'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=900&auto=format&fit=crop&q=85',
                    ],
                    [
                        'name' => 'Men Jackets & Outerwear',
                        'slug' => 'men-jackets',
                        'image' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=900&auto=format&fit=crop&q=85',
                    ],
                    [
                        'name' => 'Men Footwear & Accessories',
                        'slug' => 'men-accessories',
                        'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=900&auto=format&fit=crop&q=85',
                    ],
                ],
            ],
            'kids' => [
                'name' => 'Kids',
                'slug' => 'kids',
                'image' => 'https://images.unsplash.com/photo-1508873696983-2df5293cb395?w=900&auto=format&fit=crop&q=85',
                'subcategories' => [
                    [
                        'name' => 'Girls Eastern Wear',
                        'slug' => 'girls-eastern',
                        'image' => 'https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=900&auto=format&fit=crop&q=85',
                    ],
                    [
                        'name' => 'Boys Kurta & Eastern',
                        'slug' => 'boys-eastern',
                        'image' => 'https://images.unsplash.com/photo-1471286174890-9c112ffca56a?w=900&auto=format&fit=crop&q=85',
                    ],
                    [
                        'name' => 'Kids Accessories',
                        'slug' => 'kids-accessories',
                        'image' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=900&auto=format&fit=crop&q=85',
                    ],
                ],
            ],
        ];

        $categoryMap = [];

        foreach ($departmentDefs as $depKey => $depData) {
            $parentCat = Category::create([
                'store_id' => $store->id,
                'name' => $depData['name'],
                'slug' => $depData['slug'],
                'image' => $depData['image'],
                'parent_id' => null,
            ]);
            $categoryMap[$depData['slug']] = $parentCat;

            foreach ($depData['subcategories'] as $sub) {
                $subCat = Category::create([
                    'store_id' => $store->id,
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'image' => $sub['image'],
                    'parent_id' => $parentCat->id,
                ]);
                $categoryMap[$sub['slug']] = $subCat;
            }
        }

        // ---------------------------------------------------------------------
        // 3. PRODUCTS DATASET FOR KHAAM POSHAK
        // ---------------------------------------------------------------------
        $products = [
            // ==================== WOMEN CLOTHES ====================
            [
                'cat_slug' => 'women-clothes',
                'name' => '3-Piece Embroidered Luxury Lawn Suit with Chiffon Dupatta',
                'price' => 11490.00,
                'sale_price' => 9490.00,
                'stock' => 45,
                'sku' => 'KP-WCL-001',
                'is_featured' => true,
                'description' => 'A royal Khaam Poshak signature unstitched 3-piece suit. Featuring an intricately embroidered Egyptian Giza lawn front with resham flora, a printed lawn back & sleeves, hand-dyed cambric trousers, and a lightweight printed pure crinkle chiffon dupatta with embroidered borders.',
                'images' => [
                    'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Unstitched (3-Piece Fabric)'],
                    'color' => ['Terracotta Rust', 'Ivory Cream', 'Mustard Ochre'],
                    'fabric' => ['100% Egyptian Giza Lawn with Chiffon Dupatta'],
                    'pieces' => ['3-Piece (Shirt, Trouser, Dupatta)'],
                    'origin' => ['Woven & Embroidered in Lahore, Pakistan'],
                ],
            ],
            [
                'cat_slug' => 'women-clothes',
                'name' => 'Multani Chikankari Hand-Embroidered 2-Piece Prêt Set',
                'price' => 9990.00,
                'sale_price' => 8490.00,
                'stock' => 38,
                'sku' => 'KP-WCL-002',
                'is_featured' => true,
                'description' => 'Artisanal Multani shadow-work chikankari embroidered by heritage village artisans. Cut in an effortless A-line kurta silhouette with Schiffli lace borders, paired with matching embroidered cotton culottes.',
                'images' => [
                    'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['XS', 'S', 'M', 'L', 'XL'],
                    'color' => ['Raw Ivory', 'Sage Green', 'Powder Peach'],
                    'fabric' => ['Pure Cotton Cambric with Multani Chikankari'],
                    'pieces' => ['2-Piece (Kurta & Culotte Trousers)'],
                    'origin' => ['Hand-Embroidered in Multan, Pakistan'],
                ],
            ],
            [
                'cat_slug' => 'women-clothes',
                'name' => 'Festive Raw Silk 3-Piece Formal Suit with Organza Dupatta',
                'price' => 21500.00,
                'sale_price' => 18500.00,
                'stock' => 25,
                'sku' => 'KP-WCL-003',
                'is_featured' => true,
                'description' => 'Exquisite festive ensemble tailored in pure Korean raw silk. The neckline and sleeves feature heavy zardozi, kora, and dabka handwork with gold tilla accents. Paired with a hand-painted gold-foiled organza dupatta and raw silk cigarette pants.',
                'images' => [
                    'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['XS', 'S', 'M', 'L', 'XL'],
                    'color' => ['Deep Maroon', 'Peacock Teal', 'Onyx Noir'],
                    'fabric' => ['Pure Korean Raw Silk with Hand-Worked Organza'],
                    'pieces' => ['3-Piece (Embroidered Shirt, Pants & Dupatta)'],
                    'origin' => ['Bespoke Atelier, Lahore'],
                ],
            ],
            [
                'cat_slug' => 'women-clothes',
                'name' => 'Printed Cambric Straight Cut Daily Prêt Kurta',
                'price' => 4490.00,
                'sale_price' => 3890.00,
                'stock' => 55,
                'sku' => 'KP-WCL-004',
                'is_featured' => false,
                'description' => 'Comfortable, crisp cotton cambric casual kurta with modern ethnic motifs, boat neckline, and stitched cuff pleats. Perfect for workwear and daily casual outings.',
                'images' => [
                    'https://images.unsplash.com/photo-1620012253295-c15c429fbb3e?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['XS', 'S', 'M', 'L', 'XL'],
                    'color' => ['Terracotta Rust', 'Olive Green', 'Mustard Yellow'],
                    'fabric' => ['100% Pure Cotton Cambric'],
                    'pieces' => ['1-Piece (Stitched Kurta)'],
                    'origin' => ['Prêt Studio, Lahore'],
                ],
            ],
            [
                'cat_slug' => 'women-clothes',
                'name' => 'Block-Printed Pure Lawn 2-Piece Summer Co-ord Set',
                'price' => 6990.00,
                'sale_price' => 5990.00,
                'stock' => 32,
                'sku' => 'KP-WCL-005',
                'is_featured' => true,
                'description' => 'Handcrafted using heritage wooden block-print techniques from Sindh. Features an oversized boxy silhouette shirt paired with matching straight trousers in breathable lightweight lawn.',
                'images' => [
                    'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['XS', 'S', 'M', 'L', 'XL'],
                    'color' => ['Indigo Blue', 'Terracotta Rust', 'Off-White'],
                    'fabric' => ['100% Hand Block-Printed Lawn'],
                    'pieces' => ['2-Piece (Shirt & Trouser)'],
                    'origin' => ['Sindh Block Print Atelier'],
                ],
            ],

            // ==================== JEWELLERY ====================
            [
                'cat_slug' => 'jewellery',
                'name' => '22K Gold Plated Heritage Kundan Bridal Choker Set',
                'price' => 16500.00,
                'sale_price' => 13990.00,
                'stock' => 20,
                'sku' => 'KP-JWL-001',
                'is_featured' => true,
                'description' => 'Heirloom Mughal-inspired bridal choker meticulously set with uncut Kundan polki glass, freshwater baroque pearls, and semi-precious emerald bead drops. Finished with hand-painted meenakari on the reverse.',
                'images' => [
                    'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Adjustable Dori'],
                    'color' => ['Antique Gold & Pearl', 'Emerald Green', 'Ruby Crimson'],
                    'fabric' => ['22K Gold Foil Plating & Natural Basra Pearls'],
                    'pieces' => ['3-Piece (Choker, Earrings & Maang Tikka)'],
                    'origin' => ['Handcrafted in Anarkali Bazaar, Lahore'],
                ],
            ],
            [
                'cat_slug' => 'jewellery',
                'name' => 'Handcrafted Traditional Polki Chandbali Jhumkas',
                'price' => 7490.00,
                'sale_price' => 6290.00,
                'stock' => 30,
                'sku' => 'KP-JWL-002',
                'is_featured' => true,
                'description' => 'Crescent moon shaped Chandbali jhumkas adorned with uncut polki stones, delicate pearl clusters, and carved emerald drops. Lightweight and comfortable for festive occasions.',
                'images' => [
                    'https://images.unsplash.com/photo-1630019852942-f89202989a59?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Standard Pierced'],
                    'color' => ['Champagne Gold', 'Emerald Green', 'Royal Sapphire'],
                    'fabric' => ['Polki Glass Stone with 24K Micron Gold Wash'],
                    'pieces' => ['1-Pair (Earrings)'],
                    'origin' => ['Handmade Jewellers of Multan'],
                ],
            ],
            [
                'cat_slug' => 'jewellery',
                'name' => 'Royal Meenakari Navratan Statement Cocktail Ring',
                'price' => 4990.00,
                'sale_price' => 3990.00,
                'stock' => 40,
                'sku' => 'KP-JWL-003',
                'is_featured' => false,
                'description' => 'Traditional Navratan round cocktail ring featuring nine colorful gemstones set in an imperial lotus design, encircled by pavé crystals and vintage gold leaf embellishments.',
                'images' => [
                    'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Adjustable Size Band'],
                    'color' => ['Multicolor Navratan', 'Ruby Red', 'Emerald Green'],
                    'fabric' => ['Hand-Painted Meenakari on Brass with Gold Polish'],
                    'pieces' => ['1-Piece (Cocktail Ring)'],
                    'origin' => ['Heritage Enamellers, Lahore'],
                ],
            ],
            [
                'cat_slug' => 'jewellery',
                'name' => 'Antique Gold Plated Filigree Bridal Bangles (Pair)',
                'price' => 8990.00,
                'sale_price' => 7490.00,
                'stock' => 25,
                'sku' => 'KP-JWL-004',
                'is_featured' => true,
                'description' => 'Pair of heirloom openable antique gold karas featuring openwork filigree carvings, screw lock clasp, and embedded ruby-colored stones.',
                'images' => [
                    'https://images.unsplash.com/photo-1611591475879-114418652431?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['2.4 (Small)', '2.6 (Medium)', '2.8 (Large)'],
                    'color' => ['Antique Gold', 'Matte Finish Gold'],
                    'fabric' => ['Solid Brass with 22K Antique Micron Gold Polish'],
                    'pieces' => ['2-Piece (Pair of Karas)'],
                    'origin' => ['Lahore Heritage Crafts'],
                ],
            ],

            // ==================== ACCESSORIES (WOMEN) ====================
            [
                'cat_slug' => 'accessories',
                'name' => 'Pure Kashmiri Hand-Embroidered Pashmina Heirloom Shawl',
                'price' => 24900.00,
                'sale_price' => 21500.00,
                'stock' => 15,
                'sku' => 'KP-ACC-001',
                'is_featured' => true,
                'description' => 'A collector piece handwoven from grade-A pure Himalayan pashmina fleece. Adorned with delicate papier-mâché needle embroidery and Kashmiri tilla border accents.',
                'images' => [
                    'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Free Size (2.5 Metres Full Doshala)'],
                    'color' => ['Antique Beige & Gold', 'Burgundy Crimson', 'Jet Black'],
                    'fabric' => ['100% Certified Kashmiri Pashmina Wool with Tilla Work'],
                    'pieces' => ['1-Piece (Handwoven Shawl)'],
                    'origin' => ['Artisan Guild, Kashmir & Lahore'],
                ],
            ],
            [
                'cat_slug' => 'accessories',
                'name' => 'Hand-Embroidered Tilla & Dabka Velvet Khussa',
                'price' => 5490.00,
                'sale_price' => 4690.00,
                'stock' => 50,
                'sku' => 'KP-ACC-002',
                'is_featured' => true,
                'description' => 'Pure micro-velvet jutti with gold tilla, dabka, and pearl embellishments. Hand-stitched onto a double-cushioned vegetable-tanned genuine buffalo leather sole for all-day wedding wear comfort.',
                'images' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['36 (US 6)', '37 (US 7)', '38 (US 8)', '39 (US 9)', '40 (US 10)'],
                    'color' => ['Bridal Crimson & Gold', 'Champagne Ivory', 'Midnight Black'],
                    'fabric' => ['Velvet Upper & Genuine Leather Sole with Kundan Work'],
                    'pieces' => ['1-Pair (Handmade Khussas)'],
                    'origin' => ['Handmade in Lahore'],
                ],
            ],
            [
                'cat_slug' => 'accessories',
                'name' => 'Handcrafted Raw Silk Potli Clutch with Zardozi Work',
                'price' => 3990.00,
                'sale_price' => 3290.00,
                'stock' => 35,
                'sku' => 'KP-ACC-003',
                'is_featured' => false,
                'description' => 'Festive drawstring potli bag in pure raw silk with intricate zardozi floral motifs, pearl bead latkan tassels, and a braided gold wrist handle.',
                'images' => [
                    'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Standard Handbag'],
                    'color' => ['Emerald Green & Gold', 'Ruby Red', 'Ivory Gold'],
                    'fabric' => ['Pure Raw Silk with Pearl Tassels and Zardozi'],
                    'pieces' => ['1-Piece (Evening Potli)'],
                    'origin' => ['Lahore Atelier'],
                ],
            ],

            // ==================== MEN EASTERN WEAR ====================
            [
                'cat_slug' => 'men-eastern-wear',
                'name' => 'Royal Egyptian Cotton Stitched Kurta Shalwar Set',
                'price' => 8990.00,
                'sale_price' => 7490.00,
                'stock' => 40,
                'sku' => 'KP-MEN-001',
                'is_featured' => true,
                'description' => 'Tailored from ultra-fine 100% Egyptian Giza cotton with a crisp mercerized finish. Features a structured band collar with subtle tonal thread embroidery and mother-of-pearl buttons, paired with a matching traditional pleated shalwar.',
                'images' => [
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['S', 'M', 'L', 'XL'],
                    'color' => ['Jet Black', 'Pure White', 'Navy Slate'],
                    'fabric' => ['100% High-Thread Count Egyptian Cotton'],
                    'pieces' => ['2-Piece (Kurta & Matching Shalwar)'],
                    'origin' => ['Bespoke Tailored in Karachi'],
                ],
            ],
            [
                'cat_slug' => 'men-eastern-wear',
                'name' => 'Hand-Spun Raw Silk Festive Embroidered Kurta',
                'price' => 11500.00,
                'sale_price' => 9990.00,
                'stock' => 30,
                'sku' => 'KP-MEN-002',
                'is_featured' => true,
                'description' => 'A royal festive kurta woven in pure slub raw silk. Features delicate hand-done resham embroidery along the collar, placket, and sleeve cuffs with subtle gold metallic accents.',
                'images' => [
                    'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['S', 'M', 'L', 'XL'],
                    'color' => ['Champagne Ivory', 'Terracotta Ochre', 'Olive Slate'],
                    'fabric' => ['100% Pure Katan Raw Silk with Resham Threadwork'],
                    'pieces' => ['1-Piece (Stitched Formal Kurta)'],
                    'origin' => ['Master Artisans, Lahore'],
                ],
            ],
            [
                'cat_slug' => 'men-eastern-wear',
                'name' => 'Charcoal Grey Textured Formal Kurta Pajama',
                'price' => 7990.00,
                'sale_price' => 6890.00,
                'stock' => 45,
                'sku' => 'KP-MEN-003',
                'is_featured' => false,
                'description' => 'Modern minimalist eastern silhouette crafted from linen-cotton textured weave. Styled with clean concealed placket and paired with narrow churidar pajama.',
                'images' => [
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['S', 'M', 'L', 'XL'],
                    'color' => ['Charcoal Grey', 'Steel Blue', 'Mocha Brown'],
                    'fabric' => ['Linen-Cotton Slub Blend'],
                    'pieces' => ['2-Piece (Kurta & Slim Pajama)'],
                    'origin' => ['Karachi Eastern Atelier'],
                ],
            ],

            // ==================== MEN JACKETS & OUTERWEAR ====================
            [
                'cat_slug' => 'men-jackets',
                'name' => 'Embroidered Velvet Formal Prince Coat',
                'price' => 19500.00,
                'sale_price' => 16500.00,
                'stock' => 20,
                'sku' => 'KP-JKT-001',
                'is_featured' => true,
                'description' => 'A statement formal Prince Coat tailored in rich plush micro-velvet. Features subtle antique gold tilla embroidery along the band collar and front placket with handcrafted filigree crest buttons.',
                'images' => [
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['38 (S)', '40 (M)', '42 (L)', '44 (XL)'],
                    'color' => ['Midnight Navy', 'Jet Black', 'Burgundy Maroon'],
                    'fabric' => ['Imported Micro-Velvet with Antique Tilla Hand-Embroidery'],
                    'pieces' => ['1-Piece (Formal Prince Coat with Metal Buttons)'],
                    'origin' => ['Master Tailored, Lahore'],
                ],
            ],
            [
                'cat_slug' => 'men-jackets',
                'name' => 'Handcrafted Jamawar Banori Waistcoat',
                'price' => 9990.00,
                'sale_price' => 8490.00,
                'stock' => 30,
                'sku' => 'KP-JKT-002',
                'is_featured' => true,
                'description' => 'Bespoke waistcoat crafted in pure Banarsi handloom Jamawar. Structured with double-fused canvas chest piece and accented with velvet welt pockets and brass antique buttons.',
                'images' => [
                    'https://images.unsplash.com/photo-1593030761757-71fae45fa0e7?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['38 (S)', '40 (M)', '42 (L)', '44 (XL)'],
                    'color' => ['Burnished Gold & Black', 'Maroon Jamawar', 'Emerald Royal'],
                    'fabric' => ['Heavy Handloom Banarsi Jamawar with Satin Lining'],
                    'pieces' => ['1-Piece (Structured Formal Waistcoat)'],
                    'origin' => ['Banaras Jamawar Artisans'],
                ],
            ],
            [
                'cat_slug' => 'men-jackets',
                'name' => 'Structured Raw Silk Nehru Vest / Waistcoat',
                'price' => 8490.00,
                'sale_price' => 7290.00,
                'stock' => 25,
                'sku' => 'KP-JKT-003',
                'is_featured' => false,
                'description' => 'Effortless luxury vest in 100% slub raw silk. Mandarin band collar with horn buttons and interior silk contrast piping.',
                'images' => [
                    'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['38 (S)', '40 (M)', '42 (L)', '44 (XL)'],
                    'color' => ['Terracotta Rust', 'Forest Green', 'Onyx Noir'],
                    'fabric' => ['Pure Korean Raw Silk with Bone Horn Buttons'],
                    'pieces' => ['1-Piece (Mandarin Collar Vest)'],
                    'origin' => ['Lahore Tailoring House'],
                ],
            ],
            [
                'cat_slug' => 'men-jackets',
                'name' => 'Italian Wool Blend Winter Tuxedo Prince Coat',
                'price' => 22900.00,
                'sale_price' => 19900.00,
                'stock' => 18,
                'sku' => 'KP-JKT-004',
                'is_featured' => true,
                'description' => 'An architectural masterpiece combining European wool fabric with regal subcontinental silhouettes. Peak satin lapel with welt pocket and concealed closure.',
                'images' => [
                    'https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['38 (S)', '40 (M)', '42 (L)', '44 (XL)'],
                    'color' => ['Jet Black', 'Graphite Grey'],
                    'fabric' => ['Virgin Wool Blend with Satin Peak Lapel'],
                    'pieces' => ['1-Piece (Tailored Formal Coat)'],
                    'origin' => ['Handcrafted in Islamabad'],
                ],
            ],

            // ==================== MEN FOOTWEAR & ACCESSORIES ====================
            [
                'cat_slug' => 'men-accessories',
                'name' => 'Handcrafted Kaptaan Peshawari Chappal',
                'price' => 6490.00,
                'sale_price' => 5490.00,
                'stock' => 50,
                'sku' => 'KP-MAC-001',
                'is_featured' => true,
                'description' => 'The iconic double-gear Kaptaan style chappal crafted from full-grain vegetable-tanned oil-pullup leather. Features sturdy recycled tyre sole, hand-stitched welt, and adjustable buckle heel strap.',
                'images' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['40 (US 7)', '41 (US 8)', '42 (US 9)', '43 (US 10)', '44 (US 11)'],
                    'color' => ['Full-Grain Dark Tan', 'Mustard Ochre', 'Jet Black'],
                    'fabric' => ['100% Genuine Full-Grain Cow Leather & Tyre Sole'],
                    'pieces' => ['1-Pair (Peshawari Chappal)'],
                    'origin' => ['Artisans of Peshawar, Khyber Pakhtunkhwa'],
                ],
            ],
            [
                'cat_slug' => 'men-accessories',
                'name' => 'Traditional Men\'s Velvet Embroidered Khussa',
                'price' => 4990.00,
                'sale_price' => 4190.00,
                'stock' => 35,
                'sku' => 'KP-MAC-002',
                'is_featured' => false,
                'description' => 'Groom collection khussa in micro-velvet with delicate gold tilla and zardozi threadwork. Features curved toe cap and genuine leather sole.',
                'images' => [
                    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['40 (US 7)', '41 (US 8)', '42 (US 9)', '43 (US 10)', '44 (US 11)'],
                    'color' => ['Jet Black & Gold', 'Maroon Velvet'],
                    'fabric' => ['Velvet Upper with Gold Tilla Work & Leather Sole'],
                    'pieces' => ['1-Pair (Formal Groom Khussas)'],
                    'origin' => ['Handmade in Multan'],
                ],
            ],
            [
                'cat_slug' => 'men-accessories',
                'name' => 'Handwoven Men\'s Pure Wool Shawl (Doshala)',
                'price' => 14900.00,
                'sale_price' => 12500.00,
                'stock' => 20,
                'sku' => 'KP-MAC-003',
                'is_featured' => true,
                'description' => 'Authentic hand-spun Swati lambswool shawl with traditional geometric four-sided woven border. Warm, luxurious, and timelessly regal for winter festivities.',
                'images' => [
                    'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1584273143981-41c073dfe8f8?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['Free Size (2.7 Metres)'],
                    'color' => ['Camel Brown', 'Steel Charcoal', 'Natural Off-White'],
                    'fabric' => ['100% Pure Swati Lambswool with Four-Sided Borders'],
                    'pieces' => ['1-Piece (Men\'s Shawl)'],
                    'origin' => ['Weavers of Swat Valley'],
                ],
            ],

            // ==================== GIRLS EASTERN WEAR ====================
            [
                'cat_slug' => 'girls-eastern',
                'name' => 'Girls Festive Hand-Embroidered Chiffon Anarkali Frock',
                'price' => 6990.00,
                'sale_price' => 5890.00,
                'stock' => 25,
                'sku' => 'KP-GLS-001',
                'is_featured' => true,
                'description' => 'Festive flared Anarkali frock for young girls with delicate resham embroidery on bodice and hemline. Fully lined in soft breathable cotton lawn with matching churidar and organza dupatta.',
                'images' => [
                    'https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1508873696983-2df5293cb395?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['3-4 Y', '5-6 Y', '7-8 Y', '9-10 Y', '11-12 Y'],
                    'color' => ['Blush Pink & Gold', 'Powder Mint', 'Crimson Rose'],
                    'fabric' => ['Pure Crinkle Chiffon with Silk Slip & Organza Dupatta'],
                    'pieces' => ['3-Piece (Frock, Trousers & Dupatta)'],
                    'origin' => ['Lahore Junior Atelier'],
                ],
            ],
            [
                'cat_slug' => 'girls-eastern',
                'name' => 'Girls Multani Chikankari 2-Piece Kurti & Gharara Set',
                'price' => 5490.00,
                'sale_price' => 4690.00,
                'stock' => 30,
                'sku' => 'KP-GLS-002',
                'is_featured' => false,
                'description' => 'Sweet and lightweight cotton cambric kurti with authentic Multani chikankari shadow work, paired with a flared gharara trouser with lace edging.',
                'images' => [
                    'https://images.unsplash.com/photo-1508873696983-2df5293cb395?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['3-4 Y', '5-6 Y', '7-8 Y', '9-10 Y', '11-12 Y'],
                    'color' => ['Ivory White', 'Terracotta Peach', 'Mustard Yellow'],
                    'fabric' => ['Pure Cotton Cambric with Hand Chikankari Embroidery'],
                    'pieces' => ['2-Piece (Kurti & Flared Gharara)'],
                    'origin' => ['Handmade in Multan'],
                ],
            ],

            // ==================== BOYS KURTA & EASTERN ====================
            [
                'cat_slug' => 'boys-eastern',
                'name' => 'Boys Embroidered Festive Kurta Shalwar with Waistcoat Set',
                'price' => 7490.00,
                'sale_price' => 6290.00,
                'stock' => 30,
                'sku' => 'KP-BYS-001',
                'is_featured' => true,
                'description' => 'Complete 3-piece festive Eid set for young boys. Includes a soft combed cotton kurta, matching pleated shalwar, and a formal gold Jamawar waistcoat with metal buttons.',
                'images' => [
                    'https://images.unsplash.com/photo-1471286174890-9c112ffca56a?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1508873696983-2df5293cb395?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['3-4 Y', '5-6 Y', '7-8 Y', '9-10 Y', '11-12 Y'],
                    'color' => ['Jet Black & Gold Jamawar', 'Off-White & Maroon', 'Navy Slate'],
                    'fabric' => ['Premium Soft Cotton Kurta with Jamawar Waistcoat'],
                    'pieces' => ['3-Piece (Kurta, Shalwar & Embroidered Waistcoat)'],
                    'origin' => ['Tailored in Lahore'],
                ],
            ],
            [
                'cat_slug' => 'boys-eastern',
                'name' => 'Boys Classic Pure Cotton Summer Kurta',
                'price' => 3490.00,
                'sale_price' => 2990.00,
                'stock' => 45,
                'sku' => 'KP-BYS-002',
                'is_featured' => false,
                'description' => 'Breathable, skin-friendly 100% combed cotton kurta for daily wear, Friday prayers, and family gatherings. Features a neat band collar and front patch pocket.',
                'images' => [
                    'https://images.unsplash.com/photo-1508873696983-2df5293cb395?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1471286174890-9c112ffca56a?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['3-4 Y', '5-6 Y', '7-8 Y', '9-10 Y', '11-12 Y'],
                    'color' => ['Off-White', 'Sky Blue', 'Pistachio Green'],
                    'fabric' => ['100% Breathable Combed Cotton'],
                    'pieces' => ['1-Piece (Stitched Kurta)'],
                    'origin' => ['Karachi Prêt Kids'],
                ],
            ],

            // ==================== KIDS ACCESSORIES ====================
            [
                'cat_slug' => 'kids-accessories',
                'name' => 'Handcrafted Kids Embroidered Velvet Khussa',
                'price' => 2990.00,
                'sale_price' => 2490.00,
                'stock' => 40,
                'sku' => 'KP-KAC-001',
                'is_featured' => true,
                'description' => 'Handcrafted traditional velvet khussa designed with extra foam padding for gentle little feet. Adorned with gold dabka and sequin motifs.',
                'images' => [
                    'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=1000&auto=format&fit=crop&q=85',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1000&auto=format&fit=crop&q=85',
                ],
                'attributes' => [
                    'size' => ['26 (Kids 9)', '28 (Kids 11)', '30 (Kids 13)', '32 (Kids 1)'],
                    'color' => ['Gold & Crimson', 'Midnight Black', 'Rose Pink'],
                    'fabric' => ['Soft Velvet with Foam Cushioned Leather Insole'],
                    'pieces' => ['1-Pair (Kids Khussa)'],
                    'origin' => ['Artisan Made in Lahore'],
                ],
            ],
        ];

        foreach ($products as $pData) {
            $cat = $categoryMap[$pData['cat_slug']] ?? null;
            if (!$cat) continue;

            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $cat->id,
                'name' => $pData['name'],
                'slug' => Str::slug($pData['name']) . '-' . Str::lower(Str::random(4)),
                'price' => $pData['price'],
                'sale_price' => $pData['sale_price'] ?? null,
                'stock' => $pData['stock'] ?? 50,
                'sku' => $pData['sku'] ?? 'KP-' . strtoupper(Str::random(6)),
                'is_featured' => $pData['is_featured'] ?? false,
                'description' => $pData['description'],
                'images' => $pData['images'],
                'status' => 'active',
            ]);

            // Save attributes
            if (!empty($pData['attributes'])) {
                foreach ($pData['attributes'] as $attrName => $values) {
                    foreach ($values as $val) {
                        ProductAttribute::create([
                            'product_id' => $product->id,
                            'attribute_name' => $attrName,
                            'attribute_value' => $val,
                            'is_available' => true,
                        ]);
                    }
                }
            }
        }
    }
}
