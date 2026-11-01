<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create / Update Default System Users (Admin, Shopper, Rider)
        User::updateOrCreate(
            ['email' => 'admin@flexistore.com'],
            [
                'name' => 'Khaam Poshak Admin',
                'username' => 'admin',
                'password' => 'admin12345',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'shopper@flexistore.com'],
            [
                'name' => 'Sara Shopper',
                'username' => 'sara_shopper',
                'password' => 'shopper123',
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'rider@flexistore.com'],
            [
                'name' => 'Tariq Rider',
                'username' => 'tariq_rider',
                'password' => 'rider123',
                'role' => 'courier',
                'is_active' => true,
            ]
        );

        // 2. Run Dedicated Khaam Poshak Seeder (Single Store, Hierarchical Categories & Products)
        $this->call([
            KhaamPoshakSeeder::class,
            HandmadeCollectionSeeder::class,
            DemoRoleUsersSeeder::class,
        ]);

        // 3. Demo Pakistani Customers & Initial Sample Orders
        $store = Store::where('slug', 'khaam-poshak')->first();
        if (!$store) return;

        Customer::query()->delete();

        $customers = [
            [
                'name' => 'Ayesha Khan',
                'email' => 'ayesha.khan@gmail.com',
                'phone' => '+92 300 1234567',
                'address' => 'House 42-B, Street 7, Sector F-7/2',
                'city' => 'Islamabad',
                'country' => 'Pakistan',
            ],
            [
                'name' => 'Zainab Malik',
                'email' => 'zainab.malik@outlook.com',
                'phone' => '+92 321 9876543',
                'address' => 'Plot 18, Block C, Phase 5, DHA',
                'city' => 'Lahore',
                'country' => 'Pakistan',
            ],
            [
                'name' => 'Hamza Qureshi',
                'email' => 'hamza.q@gmail.com',
                'phone' => '+92 333 5551234',
                'address' => 'Apartment 402, Royal Residency, Clifton',
                'city' => 'Karachi',
                'country' => 'Pakistan',
            ],
        ];

        $customerModels = [];
        foreach ($customers as $c) {
            $customerModels[] = Customer::create($c);
        }

        // Demo Order 1: Festive Suit
        $suitProduct = Product::where('store_id', $store->id)->where('name', 'like', '%Suit%')->first();
        if ($suitProduct) {
            $order1 = Order::create([
                'store_id' => $store->id,
                'customer_id' => $customerModels[0]->id,
                'order_number' => 'KP-ORD-1001',
                'total_amount' => $suitProduct->price,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'final_amount' => $suitProduct->sale_price ?? $suitProduct->price,
                'status' => 'processing',
                'delivery_status' => 'assigned',
                'payment_method' => 'cod',
                'is_phone_verified' => true,
                'shipping_address' => $customerModels[0]->address . ', ' . $customerModels[0]->city,
                'phone' => $customerModels[0]->phone,
                'notes' => 'Please deliver in signature luxury box.',
            ]);
            OrderItem::create([
                'order_id' => $order1->id,
                'product_id' => $suitProduct->id,
                'product_name' => $suitProduct->name,
                'price' => $suitProduct->sale_price ?? $suitProduct->price,
                'quantity' => 1,
                'selected_attributes' => ['size' => 'M', 'color' => 'Terracotta Rust'],
                'subtotal' => $suitProduct->sale_price ?? $suitProduct->price,
            ]);
        }

        // Demo Order 2: Men Prince Coat & Khussa
        $coatProduct = Product::where('store_id', $store->id)->where('name', 'like', '%Prince Coat%')->first();
        if ($coatProduct) {
            $order2 = Order::create([
                'store_id' => $store->id,
                'customer_id' => $customerModels[1]->id,
                'order_number' => 'KP-ORD-1002',
                'total_amount' => $coatProduct->price,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'final_amount' => $coatProduct->sale_price ?? $coatProduct->price,
                'status' => 'shipped',
                'delivery_status' => 'out_for_delivery',
                'payment_method' => 'cod',
                'is_phone_verified' => true,
                'shipping_address' => $customerModels[1]->address . ', ' . $customerModels[1]->city,
                'phone' => $customerModels[1]->phone,
                'notes' => 'Call customer upon arrival.',
            ]);
            OrderItem::create([
                'order_id' => $order2->id,
                'product_id' => $coatProduct->id,
                'product_name' => $coatProduct->name,
                'price' => $coatProduct->sale_price ?? $coatProduct->price,
                'quantity' => 1,
                'selected_attributes' => ['size' => '40 (M)', 'color' => 'Midnight Navy'],
                'subtotal' => $coatProduct->sale_price ?? $coatProduct->price,
            ]);
        }
    }
}
