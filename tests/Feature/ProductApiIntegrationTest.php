<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Store;
use App\Models\Category;

class ProductApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Khaam Poshak Online',
            'slug' => 'clothing',
            'theme' => 'theme-clothing',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Handmade Pishwas',
            'slug' => 'handmade-pishwas',
        ]);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'name' => 'Artisanal Zardozi Pishwas',
            'slug' => 'artisanal-zardozi-pishwas',
            'description' => 'Pure handloom cotton with intricate zardozi needlework.',
            'price' => 14500,
            'sale_price' => 12999,
            'stock' => 10,
            'sku' => 'KHM-PIS-001',
            'images' => ['https://images.unsplash.com/photo-1.jpg', 'https://images.unsplash.com/photo-2.jpg'],
            'is_featured' => true,
            'status' => 'active',
        ]);

        // Add Multi-Size Variants with Stock Availability
        ProductAttribute::create([
            'product_id' => $this->product->id,
            'attribute_name' => 'size',
            'attribute_value' => 'S',
            'is_available' => true,
        ]);

        ProductAttribute::create([
            'product_id' => $this->product->id,
            'attribute_name' => 'size',
            'attribute_value' => 'M',
            'is_available' => true,
        ]);

        ProductAttribute::create([
            'product_id' => $this->product->id,
            'attribute_name' => 'size',
            'attribute_value' => 'L',
            'is_available' => false, // Sold out
        ]);

        // Add Color with Image Link
        ProductAttribute::create([
            'product_id' => $this->product->id,
            'attribute_name' => 'color',
            'attribute_value' => 'Terracotta Rust',
            'is_available' => true,
            'image_url' => 'https://images.unsplash.com/photo-1.jpg',
        ]);
    }

    public function test_can_fetch_active_store(): void
    {
        $response = $this->getJson('/api/store/active');

        $response->assertStatus(200);
        $response->assertJsonPath('store.id', $this->store->id);
        $response->assertJsonPath('store.slug', 'clothing');
        $response->assertJsonPath('store_type', 'clothing');
    }

    public function test_can_list_products_filtered_by_store(): void
    {
        $response = $this->getJson('/api/products?store=clothing');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'stock',
                    'category',
                    'attributes',
                ],
            ],
        ]);
        $response->assertJsonPath('data.0.slug', 'artisanal-zardozi-pishwas');
    }

    public function test_can_fetch_single_product_with_detailed_availability_attributes(): void
    {
        $response = $this->getJson("/api/products/{$this->product->slug}");

        $response->assertStatus(200);
        $response->assertJsonPath('product.slug', 'artisanal-zardozi-pishwas');
        $response->assertJsonPath('product.name', 'Artisanal Zardozi Pishwas');

        // Verify grouped_attributes_detailed contains multi-size with availability flags
        $sizes = $response->json('grouped_attributes_detailed.size');
        $this->assertIsArray($sizes);
        $this->assertCount(3, $sizes);

        $sizeS = collect($sizes)->firstWhere('value', 'S');
        $sizeM = collect($sizes)->firstWhere('value', 'M');
        $sizeL = collect($sizes)->firstWhere('value', 'L');

        $this->assertTrue($sizeS['is_available']);
        $this->assertTrue($sizeM['is_available']);
        $this->assertFalse($sizeL['is_available']); // Out of stock size
    }

    public function test_non_existent_product_returns_404(): void
    {
        $response = $this->getJson('/api/products/non-existent-product-slug');

        $response->assertStatus(404);
    }

    public function test_search_finds_products_by_keyword_and_plural_variant(): void
    {
        // Add a second product: Traditional Cap
        $capCategory = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Crochet Caps',
            'slug' => 'crochet-caps',
        ]);

        $capProduct = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $capCategory->id,
            'name' => 'Maroon Hand-Knitted Textured Traditional Cap',
            'slug' => 'maroon-hand-knitted-textured-traditional-cap',
            'description' => 'A domed traditional cap with geometric stitches.',
            'price' => 700,
            'stock' => 8,
            'sku' => 'CAP-001',
            'images' => ['https://images.unsplash.com/photo-cap.jpg'],
            'status' => 'active',
        ]);

        // 1. Singular search: "cap"
        $resSingular = $this->getJson('/api/products?search=cap');
        $resSingular->assertStatus(200);
        $this->assertTrue(collect($resSingular->json('data'))->contains('slug', 'maroon-hand-knitted-textured-traditional-cap'));

        // 2. Plural search: "caps" (should still match the singular product name "Cap")
        $resPlural = $this->getJson('/api/products?search=caps');
        $resPlural->assertStatus(200);
        $this->assertTrue(collect($resPlural->json('data'))->contains('slug', 'maroon-hand-knitted-textured-traditional-cap'));
    }

    public function test_search_finds_products_by_multi_word_tokens(): void
    {
        $pishwasCategory = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Festive Wear',
            'slug' => 'festive-wear',
        ]);

        Product::create([
            'store_id' => $this->store->id,
            'category_id' => $pishwasCategory->id,
            'name' => 'Deep Blue Hand-Knitted Textured Traditional Cap',
            'slug' => 'deep-blue-cap',
            'description' => 'Rich royal blue domed cultural cap.',
            'price' => 800,
            'stock' => 5,
            'sku' => 'CAP-BLU-001',
            'images' => ['https://images.unsplash.com/photo-blue.jpg'],
            'status' => 'active',
        ]);

        // Multi-word search: "blue cap" (words are non-contiguous in the name)
        $response = $this->getJson('/api/products?search=blue%20cap');
        $response->assertStatus(200);
        $this->assertTrue(collect($response->json('data'))->contains('slug', 'deep-blue-cap'));
    }

    public function test_search_finds_products_by_attribute_or_category_name(): void
    {
        // Search by category name "Crochet" or attribute "Terracotta"
        $resCategory = $this->getJson('/api/products?search=Pishwas');
        $resCategory->assertStatus(200);
        $this->assertTrue(collect($resCategory->json('data'))->contains('slug', 'artisanal-zardozi-pishwas'));

        $resAttr = $this->getJson('/api/products?search=Terracotta');
        $resAttr->assertStatus(200);
        $this->assertTrue(collect($resAttr->json('data'))->contains('slug', 'artisanal-zardozi-pishwas'));
    }
}
