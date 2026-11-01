<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Store;
use App\Models\Category;

class ProductAttributeTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_attributes_cast_boolean_availability(): void
    {
        $store = Store::create([
            'name' => 'Khaam Poshak Online',
            'slug' => 'clothing',
            'theme' => 'theme-clothing',
            'is_active' => true,
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Handmade Pishwas',
            'slug' => 'handmade-pishwas',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Artisanal Zardozi Pishwas',
            'slug' => 'artisanal-zardozi-pishwas',
            'description' => 'Pure handloom cotton with intricate zardozi needlework.',
            'price' => 14500,
            'stock' => 10,
            'sku' => 'KHM-PIS-001',
            'images' => ['https://images.unsplash.com/photo-1.jpg'],
            'status' => 'active',
        ]);

        $attrInStock = ProductAttribute::create([
            'product_id' => $product->id,
            'attribute_name' => 'size',
            'attribute_value' => 'M',
            'is_available' => true,
        ]);

        $attrSoldOut = ProductAttribute::create([
            'product_id' => $product->id,
            'attribute_name' => 'size',
            'attribute_value' => 'L',
            'is_available' => false,
        ]);

        $this->assertTrue($attrInStock->is_available);
        $this->assertFalse($attrSoldOut->is_available);
        $this->assertEquals(2, $product->attributes()->count());
        $this->assertEquals('size', $attrInStock->attribute_name);
        $this->assertEquals('M', $attrInStock->attribute_value);
    }
}
