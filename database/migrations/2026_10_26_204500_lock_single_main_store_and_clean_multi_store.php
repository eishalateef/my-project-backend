<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clean out the non-main store (Store 14 / kitchen mart) and any other secondary stores
        $secondaryStores = Store::where('id', '!=', 13)->get();
        foreach ($secondaryStores as $secStore) {
            // Delete product attributes for secondary store's products
            $secProductIds = Product::where('store_id', $secStore->id)->pluck('id');
            if ($secProductIds->isNotEmpty()) {
                DB::table('product_attributes')->whereIn('product_id', $secProductIds)->delete();
                DB::table('products')->whereIn('id', $secProductIds)->delete();
            }
            // Delete categories for secondary store
            Category::where('store_id', $secStore->id)->delete();
            // Delete the secondary store itself
            $secStore->delete();
        }

        // 2. Ensure Store 13 (KHAAM POSHAK) is permanently active and properly configured
        $mainStore = Store::find(13);
        if ($mainStore) {
            $mainStore->update([
                'name' => 'KHAAM POSHAK',
                'store_type' => 'clothing',
                'is_active' => true,
            ]);
        } else {
            // Fallback: If id is different, activate the clothing store
            $clothingStore = Store::where('store_type', 'clothing')->first();
            if ($clothingStore) {
                $clothingStore->update(['is_active' => true]);
            }
        }

        // 3. Clear all caches
        Cache::flush();
    }

    public function down(): void
    {
        // No down migration needed as single-store lock is intentional
    }
};
