<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'categories_list_' . md5(json_encode($request->all()));

        $categories = Cache::remember($cacheKey, 180, function () use ($request) {
            $query = Category::withCount('products')->with([
                'subcategories' => function ($q) {
                    $q->withCount('products')->with(['subcategories' => function ($sq) {
                        $sq->withCount('products');
                    }]);
                },
                'parent',
            ]);

            $switcherEnabled = (bool) config('flexistore.enable_store_switcher', false);
            $devKey = $request->header('X-Developer-Key') ?? $request->query('dev_key') ?? $request->input('dev_key');
            $secret = config('flexistore.developer_secret_key', 'flexi_dev_secret_2026');
            $isDev = !empty($devKey) && hash_equals($secret, (string) $devKey);

            if (!$switcherEnabled && !$isDev) {
                $activeStoreId = Store::where('is_active', true)->value('id') ?? 13;
                $query->where('categories.store_id', $activeStoreId);
            } elseif ($request->filled('store_id')) {
                $query->where('categories.store_id', $request->store_id);
            } else {
                $activeStoreId = Store::where('is_active', true)->value('id') ?? 13;
                $query->where('categories.store_id', $activeStoreId);
            }

            if ($request->boolean('parents_only')) {
                $query->whereNull('parent_id');
            } elseif ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            $query->orderBy('id', 'asc');
            return $query->get();
        });

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create([
            'store_id' => $validated['store_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'image' => $validated['image'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category->update(array_filter([
            'name' => $validated['name'] ?? $category->name,
            'image' => array_key_exists('image', $validated) ? $validated['image'] : $category->image,
            'parent_id' => array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $category->parent_id,
        ], function ($val) { return !is_null($val); }));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Admin: Upload category cover image from PC
     */
    public function uploadImage(Request $request)
    {
        $file = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
        } elseif ($request->hasFile('images')) {
            $raw = $request->file('images');
            $file = is_array($raw) ? $raw[0] : $raw;
        }

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'No image file detected. Please select an image from your computer.',
            ], 422);
        }

        if (!$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file was invalid or corrupted.',
            ], 422);
        }

        $allowedMimes = ['jpeg', 'png', 'jpg', 'webp', 'gif', 'svg', 'avif', 'bmp'];
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($ext, $allowedMimes, true)) {
            return response()->json([
                'success' => false,
                'message' => "File format '.{$ext}' is not supported. Please upload JPG, PNG, WEBP, GIF, or SVG images.",
            ], 422);
        }

        $maxBytes = 12 * 1024 * 1024; // 12 MB max
        if ($file->getSize() > $maxBytes) {
            return response()->json([
                'success' => false,
                'message' => "Image exceeds the maximum allowed size of 12MB.",
            ], 422);
        }

        $destinationPath = public_path('uploads/categories');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $filename = 'cat_' . Str::random(16) . '_' . time() . '.' . $ext;
        $file->move($destinationPath, $filename);

        $uploadedPath = '/uploads/categories/' . $filename;

        return response()->json([
            'success' => true,
            'message' => 'Category image uploaded successfully from PC.',
            'url' => $uploadedPath,
        ]);
    }
}
