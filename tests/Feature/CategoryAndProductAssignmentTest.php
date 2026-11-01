<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;

class CategoryAndProductAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_120_typo_fix_and_jewellery_assignment(): void
    {
        $store = Store::create([
            'name' => 'Khaam Poshak Online',
            'slug' => 'clothing',
            'theme' => 'theme-clothing',
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\DB::table('categories')->insert([
            [
                'id' => 84,
                'store_id' => $store->id,
                'name' => 'Women',
                'slug' => 'women',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 120,
                'store_id' => $store->id,
                'name' => 'Winter Waffler',
                'slug' => 'winter-waffler-e47B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 117,
                'store_id' => $store->id,
                'name' => 'Jewellery',
                'slug' => 'jewellery-6odb',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \Illuminate\Support\Facades\DB::table('products')->insert([
            'id' => 201,
            'store_id' => $store->id,
            'category_id' => 84,
            'name' => 'Rangoli Thread Bangles (Set of 6)',
            'slug' => 'rangoli-thread-bangles-set-of-6',
            'description' => 'Vibrant handcrafted silk thread bangles',
            'price' => 1850,
            'stock' => 15,
            'sku' => 'KP-JW-001',
            'images' => json_encode(['https://images.unsplash.com/photo-1.jpg']),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Execute migration logic
        $migration = require database_path('migrations/2026_10_24_161500_fix_category_typo_and_bangles_assignment.php');
        $migration->up();

        $this->assertEquals('Handmade Mufflers & Scarves', Category::find(120)->name);
        $this->assertEquals('handmade-mufflers-scarves', Category::find(120)->slug);
        $this->assertEquals(117, Product::find(201)->category_id);
    }
}
