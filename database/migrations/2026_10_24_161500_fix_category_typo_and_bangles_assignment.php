<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Category;
use App\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix Category 120 typo from 'Winter Waffler' to 'Handmade Mufflers & Scarves'
        $category = Category::find(120);
        if ($category) {
            $category->update([
                'name' => 'Handmade Mufflers & Scarves',
                'slug' => 'handmade-mufflers-scarves',
            ]);
        }

        // 2. Reassign Product 201 'Rangoli Thread Bangles' from 84 (Women) to 117 (Jewellery)
        $product = Product::find(201);
        if ($product) {
            $product->update([
                'category_id' => 117,
            ]);
        }
    }

    public function down(): void
    {
        $category = Category::find(120);
        if ($category) {
            $category->update([
                'name' => 'Winter Waffler',
                'slug' => 'winter-waffler-t4kF',
            ]);
        }

        $product = Product::find(201);
        if ($product) {
            $product->update([
                'category_id' => 84,
            ]);
        }
    }
};
