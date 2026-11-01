<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;

class OrderCheckoutIntegrationTest extends TestCase
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
            'name' => 'Handmade Kurtas',
            'slug' => 'handmade-kurtas',
        ]);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'name' => 'Embroidered Raw Silk Kurta',
            'slug' => 'embroidered-raw-silk-kurta',
            'description' => 'Pure handloom raw silk kurta.',
            'price' => 12000,
            'sale_price' => 9999,
            'stock' => 5,
            'sku' => 'KHM-SLK-001',
            'images' => ['https://images.unsplash.com/photo-1.jpg'],
            'is_featured' => true,
            'status' => 'active',
        ]);
    }

    public function test_checkout_validation_requires_essential_fields(): void
    {
        $response = $this->postJson('/api/checkout', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['store_id', 'name', 'phone', 'address', 'city', 'payment_method', 'items']);
    }

    public function test_successful_checkout_creates_order_and_decrements_stock(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'name' => 'Fatima Noor',
            'phone' => '+923001234567',
            'email' => 'fatima@example.com',
            'address' => 'House 42, Street 7, Phase 5 DHA',
            'city' => 'Lahore',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'selected_attributes' => [
                        'size' => 'M',
                        'color' => 'Crimson Red',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('order.total_amount', 19998); // 9999 * 2
        $response->assertJsonPath('order.final_amount', 19998);

        // Check stock was decremented from 5 to 3
        $this->assertEquals(3, $this->product->fresh()->stock);

        // Verify order number exists
        $orderNumber = $response->json('order.order_number');
        $this->assertNotEmpty($orderNumber);

        // Track order endpoint lookup
        $trackResponse = $this->postJson('/api/orders/track', [
            'order_number' => $orderNumber,
            'phone' => '+923001234567',
        ]);

        $trackResponse->assertStatus(200);
        $trackResponse->assertJsonPath('success', true);
        $trackResponse->assertJsonPath('order.order_number', $orderNumber);
    }

    public function test_checkout_fails_when_quantity_exceeds_available_stock(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'name' => 'Ahmed Khan',
            'phone' => '+923219876543',
            'address' => 'Apartment 12B, Clifton',
            'city' => 'Karachi',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 99, // Exceeds stock (5)
                ],
            ],
        ];

        $response = $this->postJson('/api/checkout', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
        // Stock must not change
        $this->assertEquals(5, $this->product->fresh()->stock);
    }
}
