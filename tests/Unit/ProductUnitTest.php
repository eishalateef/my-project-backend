<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;

class ProductUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_model_attributes_and_relations(): void
    {
        $store = Store::create([
            'name' => 'Khaam Poshak',
            'slug' => 'clothing',
            'theme' => 'theme-clothing',
            'is_active' => true,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Handloom Kurtas',
            'slug' => 'handloom-kurtas',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Classic Khaddar Kurta',
            'slug' => 'classic-khaddar-kurta',
            'description' => 'Pure hand-spun cotton khaddar kurta.',
            'price' => 7500,
            'sale_price' => 5999,
            'stock' => 15,
            'sku' => 'KHM-KHD-001',
            'images' => ['https://images.unsplash.com/photo-kurta.jpg'],
            'is_featured' => true,
            'status' => 'active',
        ]);

        $this->assertEquals('Classic Khaddar Kurta', $product->name);
        $this->assertEquals(7500.0, $product->price);
        $this->assertEquals(5999.0, $product->sale_price);
        $this->assertEquals(15, $product->stock);
        $this->assertTrue($product->is_featured);
        $this->assertIsArray($product->images);
        $this->assertEquals($store->id, $product->store->id);
        $this->assertEquals($category->id, $product->category->id);
    }
}
