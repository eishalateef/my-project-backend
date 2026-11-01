<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    /**
     * Check if the current request has developer master privileges
     */
    protected function isDeveloperAuthorized(Request $request): bool
    {
        $devKey = $request->header('X-Developer-Key') ?? $request->query('dev_key') ?? $request->input('dev_key');
        $secret = config('flexistore.developer_secret_key', 'flexi_dev_secret_2026');
        return !empty($devKey) && hash_equals($secret, (string) $devKey);
    }

    /**
     * Get currently active main store with configuration and dynamic filters
     */
    public function getActiveStore(Request $request)
    {
        $data = Cache::remember('active_main_store_payload', 180, function () {
            $store = Store::with(['categories' => function ($q) {
                $q->whereNull('parent_id')->with('subcategories');
            }])->where('is_active', true)->first() ?? Store::first();

            if (!$store) {
                return null;
            }

            // High-performance direct INNER JOIN instead of correlated whereHas subquery
            $availableAttributes = DB::table('product_attributes')
                ->join('products', 'products.id', '=', 'product_attributes.product_id')
                ->where('products.store_id', $store->id)
                ->where('products.status', 'active')
                ->select('product_attributes.attribute_name', 'product_attributes.attribute_value')
                ->distinct()
                ->get()
                ->groupBy('attribute_name')
                ->map(function ($items) {
                    return $items->pluck('attribute_value')->unique()->values();
                });

            return [
                'store' => $store,
                'available_attributes' => $availableAttributes,
                'theme_key' => $store->theme_key ?? 'theme-clothing',
                'store_type' => 'clothing',
                'permissions' => [
                    'enable_store_switcher' => false,
                    'is_developer_unlocked' => false,
                    'default_store_type' => 'clothing',
                ],
            ];
        });

        if (!$data) {
            return response()->json(['message' => 'No store available.'], 404);
        }

        return response()->json($data);
    }

    /**
     * List stores: Dedicated single-store setup. Returns only the main store.
     */
    public function listStores(Request $request)
    {
        $stores = Store::where('is_active', true)
            ->withCount(['products', 'categories', 'orders'])
            ->get();

        if ($stores->isEmpty()) {
            $stores = Store::take(1)->withCount(['products', 'categories', 'orders'])->get();
        }

        return response()->json(['stores' => $stores]);
    }

    /**
     * Developer Master Unlock (Disabled - Dedicated Single Store)
     */
    public function verifyDeveloperKey(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Store switcher is disabled. This is a dedicated single-store application.',
        ], 403);
    }

    /**
     * Store Switcher (Disabled - Dedicated Single Store)
     */
    public function switchStore(Request $request)
    {
        return response()->json([
            'success' => false,
            'is_locked' => true,
            'message' => 'Store switching is disabled. This is a dedicated single-store application.',
        ], 403);
    }

    /**
     * Update store general settings (name, currency, tagline)
     */
    public function updateStoreSettings(Request $request, $id)
    {
        $store = Store::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'currency' => 'sometimes|string|max:10',
            'currency_symbol' => 'sometimes|string|max:10',
        ]);

        $store->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Store settings updated successfully',
            'store' => $store,
        ]);
    }
}
