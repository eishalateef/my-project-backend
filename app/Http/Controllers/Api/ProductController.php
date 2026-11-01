<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Get products with multi-attribute filtering, category filtering, search, and sorting
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'store', 'attributes'])->where('status', 'active');

        $switcherEnabled = (bool) config('flexistore.enable_store_switcher', false);
        $devKey = $request->header('X-Developer-Key') ?? $request->query('dev_key') ?? $request->input('dev_key');
        $secret = config('flexistore.developer_secret_key', 'flexi_dev_secret_2026');
        $isDev = !empty($devKey) && hash_equals($secret, (string) $devKey);

        // Store filtering: high-performance direct indexed store_id filter
        if (!$switcherEnabled && !$isDev) {
            $activeStoreId = Cache::remember('active_store_id', 300, function () {
                return Store::where('is_active', true)->value('id') ?? 13;
            });
            $query->where('products.store_id', $activeStoreId);
        } elseif ($request->filled('store_id')) {
            $query->where('products.store_id', $request->store_id);
        } elseif ($request->filled('store_type')) {
            $storeIdForType = Store::where('store_type', $request->store_type)->value('id');
            if ($storeIdForType) {
                $query->where('products.store_id', $storeIdForType);
            }
        } else {
            $activeStoreId = Cache::remember('active_store_id', 300, function () {
                return Store::where('is_active', true)->value('id') ?? 13;
            });
            $query->where('products.store_id', $activeStoreId);
        }

        // Category filter: supports filtering by parent department (e.g. 'women', 'men', 'kids'), 'all-handmade', or specific subcategory
        $categorySlug = $request->input('category_slug') ?: $request->input('category');
        $isHandmadeOnly = $request->boolean('handmade') || in_array($categorySlug, ['handmade', 'all-handmade', 'shop-all-handmade'], true);

        if ($isHandmadeOnly) {
            // Retrieve all handmade category branches across Men, Women, Kids
            $handmadeCats = \App\Models\Category::where('slug', 'like', '%handmade%')->get();
            $allHandmadeIds = [];
            foreach ($handmadeCats as $hc) {
                $allHandmadeIds = array_merge($allHandmadeIds, $hc->getDescendantsAndSelfIds());
            }
            $allHandmadeIds = array_unique($allHandmadeIds);

            $query->where(function ($subQ) use ($allHandmadeIds) {
                if (!empty($allHandmadeIds)) {
                    $subQ->whereIn('category_id', $allHandmadeIds);
                }
                $subQ->orWhereHas('attributes', function ($aq) {
                    $aq->where('attribute_name', 'craftsmanship')
                       ->orWhere('attribute_name', 'is_handmade')
                       ->orWhere('attribute_value', 'like', '%handmade%')
                       ->orWhere('attribute_value', 'like', '%hand-embroidered%')
                       ->orWhere('attribute_value', 'like', '%crochet%');
                });
            });
        } elseif ($request->filled('category_id')) {
            $catId = $request->category_id;
            $targetCat = \App\Models\Category::find($catId);
            if ($targetCat) {
                $query->whereIn('category_id', $targetCat->getDescendantsAndSelfIds());
            } else {
                $query->where('category_id', $catId);
            }
        } elseif (!empty($categorySlug) && $categorySlug !== 'all') {
            $targetCat = \App\Models\Category::where('slug', $categorySlug)->first();
            if ($targetCat) {
                $query->whereIn('category_id', $targetCat->getDescendantsAndSelfIds());
            } else {
                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }
        }

        // Subcategory or product type attribute filter (e.g. ?subcategory=Hand-Embroidered+Kurtas)
        if ($request->filled('subcategory')) {
            $subcatVal = $request->subcategory;
            $query->where(function ($q) use ($subcatVal) {
                $q->whereHas('category', function ($cq) use ($subcatVal) {
                    $cq->where('name', 'like', "%{$subcatVal}%")
                       ->orWhere('slug', 'like', "%" . Str::slug($subcatVal) . "%");
                })->orWhereHas('attributes', function ($aq) use ($subcatVal) {
                    $aq->where('attribute_value', 'like', "%{$subcatVal}%");
                });
            });
        }

        // Featured only
        if ($request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }

        // Search by name, SKU, category, attributes, or description with tokenized multi-word & singular/plural matching
        if ($request->filled('search')) {
            $rawSearch = trim((string) $request->search);
            $tokens = array_filter(preg_split('/\s+/', $rawSearch));

            if (!empty($tokens)) {
                $query->where(function ($mainQ) use ($rawSearch, $tokens) {
                    // 1. Exact full phrase match in name or SKU
                    $mainQ->where('products.name', 'like', "%{$rawSearch}%")
                          ->orWhere('products.sku', 'like', "%{$rawSearch}%");

                    // 2. Tokenized match: every keyword must match somewhere across name, category, attributes, or description
                    $mainQ->orWhere(function ($allTokensQ) use ($tokens) {
                        foreach ($tokens as $token) {
                            $cleanToken = trim($token);
                            if (empty($cleanToken)) continue;

                            // Generate singular & plural forms (e.g. 'caps' <-> 'cap', 'shawls' <-> 'shawl')
                            $variants = [$cleanToken];
                            $lower = strtolower($cleanToken);
                            if (str_ends_with($lower, 's') && strlen($lower) > 3) {
                                $variants[] = substr($cleanToken, 0, -1);
                            } else {
                                $variants[] = $cleanToken . 's';
                            }

                            $allTokensQ->where(function ($subQ) use ($variants) {
                                foreach ($variants as $v) {
                                    $subQ->orWhere('products.name', 'like', "%{$v}%")
                                         ->orWhere('products.sku', 'like', "%{$v}%")
                                         ->orWhereHas('category', function ($cq) use ($v) {
                                             $cq->where('name', 'like', "%{$v}%")
                                                ->orWhere('slug', 'like', "%{$v}%");
                                         })
                                         ->orWhereHas('attributes', function ($aq) use ($v) {
                                             $aq->where('attribute_value', 'like', "%{$v}%")
                                                ->orWhere('attribute_name', 'like', "%{$v}%");
                                         })
                                         // Word-boundary matching in description so words like "capable" don't falsely match "cap"
                                         ->orWhere('products.description', 'like', "% {$v} %")
                                         ->orWhere('products.description', 'like', "{$v} %")
                                         ->orWhere('products.description', 'like', "% {$v}.")
                                         ->orWhere('products.description', 'like', "% {$v},")
                                         ->orWhere('products.description', 'like', "% {$v}");
                                }
                            });
                        }
                    });
                });
            }
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Dynamic attribute filters: e.g. ?attributes[size]=XL&attributes[color]=Black or ?color=Camel
        $reqAttributes = $request->input('attributes');
        if (!empty($reqAttributes) && is_array($reqAttributes)) {
            foreach ($reqAttributes as $attrName => $attrValue) {
                if (!empty($attrValue)) {
                    $values = is_array($attrValue) ? $attrValue : explode(',', $attrValue);
                    $query->whereHas('attributes', function ($q) use ($attrName, $values) {
                        $q->where('attribute_name', $attrName)
                          ->where(function ($subQ) use ($values) {
                              foreach ($values as $val) {
                                  $cleanVal = trim($val);
                                  if (!empty($cleanVal)) {
                                      $subQ->orWhere('attribute_value', $cleanVal)
                                           ->orWhere('attribute_value', 'like', "%{$cleanVal}%");
                                  }
                              }
                          });
                    });
                }
            }
        }

        // Direct attribute query params fallback (e.g. ?color=Camel or ?size=M or ?fabric=Linen)
        foreach (['color', 'fabric', 'size'] as $directKey) {
            if ($request->filled($directKey)) {
                $dirVal = $request->input($directKey);
                $values = is_array($dirVal) ? $dirVal : explode(',', $dirVal);
                $query->whereHas('attributes', function ($q) use ($directKey, $values) {
                    $q->where('attribute_name', $directKey)
                      ->where(function ($subQ) use ($values) {
                          foreach ($values as $val) {
                              $cleanVal = trim($val);
                              if (!empty($cleanVal)) {
                                  $subQ->orWhere('attribute_value', $cleanVal)
                                       ->orWhere('attribute_value', 'like', "%{$cleanVal}%");
                              }
                          }
                      });
                });
            }
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->latest();
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $perPage = (int) $request->get('per_page', 16);
        $cacheKey = 'products_p_' . md5(json_encode($request->all()));

        $products = Cache::remember($cacheKey, 60, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });

        return response()->json($products);
    }

    /**
     * Get single product with details, grouped attributes, and related recommendations
     */
    public function show($idOrSlug)
    {
        $product = Product::with(['category', 'store', 'attributes'])
            ->where(function ($query) use ($idOrSlug) {
                if (is_numeric($idOrSlug)) {
                    $query->where('id', (int) $idOrSlug)->orWhere('slug', $idOrSlug);
                } else {
                    $query->where('slug', $idOrSlug);
                }
            })
            ->firstOrFail();

        // Group attributes cleanly for frontend selectors
        $groupedAttributes = $product->attributes->groupBy('attribute_name')->map(function ($items) {
            return $items->pluck('attribute_value')->values();
        });

        // Group attributes with detailed availability status (in-stock / sold-out) and color pictures
        $groupedAttributesDetailed = $product->attributes->groupBy('attribute_name')->map(function ($items) {
            return $items->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'value' => $attr->attribute_value,
                    'is_available' => (bool) $attr->is_available,
                    'image_url' => $attr->image_url,
                ];
            })->values();
        });

        // Related products in same category (with eager loaded relations)
        $relatedProducts = Product::with(['category', 'store', 'attributes'])
            ->where('store_id', $product->store_id)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->take(4)
            ->get();

        return response()->json([
            'product' => $product,
            'grouped_attributes' => $groupedAttributes,
            'grouped_attributes_detailed' => $groupedAttributesDetailed,
            'related_products' => $relatedProducts,
        ]);
    }

    /**
     * Admin: Create new product with dynamic attributes
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:price|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|max:100|unique:products,sku',
            'images' => 'required|array|min:1',
            'images.*' => 'required|string',
            'is_featured' => 'boolean',
            'status' => 'in:active,inactive',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required_with:attributes|string|max:100',
            'attributes.*.value' => 'required_with:attributes|string|max:255',
            'attributes.*.is_available' => 'nullable|boolean',
            'attributes.*.image_url' => 'nullable|string',
        ]);

        $product = DB::transaction(function () use ($validated, $request) {
            $prod = Product::create([
                'store_id' => $validated['store_id'],
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'description' => $validated['description'],
                'price' => $validated['price'],
                'sale_price' => $validated['sale_price'] ?? null,
                'stock' => $validated['stock'],
                'sku' => strtoupper($validated['sku']),
                'images' => $validated['images'],
                'is_featured' => $request->boolean('is_featured'),
                'status' => $validated['status'] ?? 'active',
            ]);

            if (!empty($validated['attributes'])) {
                foreach ($validated['attributes'] as $attr) {
                    ProductAttribute::create([
                        'product_id' => $prod->id,
                        'attribute_name' => strtolower(trim($attr['name'])),
                        'attribute_value' => trim($attr['value']),
                        'is_available' => isset($attr['is_available']) ? (bool) $attr['is_available'] : true,
                        'image_url' => !empty($attr['image_url']) ? trim($attr['image_url']) : null,
                    ]);
                }
            }

            return $prod->load(['category', 'attributes', 'store']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    /**
     * Admin: Update product and dynamic attributes
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $product->id,
            'images' => 'sometimes|array|min:1',
            'images.*' => 'string',
            'is_featured' => 'boolean',
            'status' => 'in:active,inactive',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required_with:attributes|string|max:100',
            'attributes.*.value' => 'required_with:attributes|string|max:255',
            'attributes.*.is_available' => 'nullable|boolean',
            'attributes.*.image_url' => 'nullable|string',
        ]);

        $updatedProduct = DB::transaction(function () use ($product, $validated, $request) {
            $product->update([
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'name' => $validated['name'] ?? $product->name,
                'slug' => isset($validated['name']) ? Str::slug($validated['name']) . '-' . $product->id : $product->slug,
                'description' => $validated['description'] ?? $product->description,
                'price' => $validated['price'] ?? $product->price,
                'sale_price' => array_key_exists('sale_price', $validated) ? $validated['sale_price'] : $product->sale_price,
                'stock' => $validated['stock'] ?? $product->stock,
                'sku' => isset($validated['sku']) ? strtoupper($validated['sku']) : $product->sku,
                'images' => $validated['images'] ?? $product->images,
                'is_featured' => $request->has('is_featured') ? $request->boolean('is_featured') : $product->is_featured,
                'status' => $validated['status'] ?? $product->status,
            ]);

            if (isset($validated['attributes'])) {
                $product->attributes()->delete();
                foreach ($validated['attributes'] as $attr) {
                    ProductAttribute::create([
                        'product_id' => $product->id,
                        'attribute_name' => strtolower(trim($attr['name'])),
                        'attribute_value' => trim($attr['value']),
                        'is_available' => isset($attr['is_available']) ? (bool) $attr['is_available'] : true,
                        'image_url' => !empty($attr['image_url']) ? trim($attr['image_url']) : null,
                    ]);
                }
            }

            return $product->fresh(['category', 'attributes', 'store']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $updatedProduct,
        ]);
    }

    /**
     * Admin: Delete product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Admin: Upload multiple product images from PC
     */
    public function uploadImages(Request $request)
    {
        $files = [];
        if ($request->hasFile('images')) {
            $raw = $request->file('images');
            $files = is_array($raw) ? $raw : [$raw];
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'No image files detected in the upload request. Please select files from your computer.',
            ], 422);
        }

        $allowedMimes = ['jpeg', 'png', 'jpg', 'webp', 'gif', 'svg', 'avif', 'bmp'];
        $maxBytes = 12 * 1024 * 1024; // 12 MB max

        $destinationPath = public_path('uploads/products');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $uploadedPaths = [];

        foreach ($files as $file) {
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One of the uploaded files was invalid or corrupted.',
                ], 422);
            }

            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (!in_array($ext, $allowedMimes, true)) {
                return response()->json([
                    'success' => false,
                    'message' => "File format '.{$ext}' is not supported. Please upload JPG, PNG, WEBP, GIF, or SVG images.",
                ], 422);
            }

            if ($file->getSize() > $maxBytes) {
                return response()->json([
                    'success' => false,
                    'message' => "Image '{$file->getClientOriginalName()}' exceeds the maximum allowed size of 12MB.",
                ], 422);
            }

            $filename = 'prod_' . Str::random(16) . '_' . time() . '.' . $ext;
            $file->move($destinationPath, $filename);

            $uploadedPaths[] = '/uploads/products/' . $filename;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedPaths) . ' image(s) uploaded successfully from PC.',
            'urls' => $uploadedPaths,
        ]);
    }
}
