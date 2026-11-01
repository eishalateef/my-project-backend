<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoRoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'khaam-poshak')->first() ?? Store::first();
        if (!$store) {
            $this->command->error("No active store found for demo users.");
            return;
        }

        // =========================================================================
        // 1. DEDICATED DEMO CUSTOMER ACCOUNT
        // =========================================================================
        $customerUser = User::updateOrCreate(
            ['email' => 'customer@demo.com'],
            [
                'name' => 'Sara Shopper (Demo Customer)',
                'username' => 'demo_customer',
                'password' => Hash::make('demo123'),
                'role' => 'customer',
                'phone' => '+92 300 1234567',
                'address' => 'House 42-B, Street 7, Sector F-7/2',
                'city' => 'Islamabad',
                'is_active' => true,
            ]
        );

        // Also ensure customer model exists
        $customerRecord = Customer::updateOrCreate(
            ['email' => 'customer@demo.com'],
            [
                'name' => 'Sara Shopper (Demo Customer)',
                'phone' => '+92 300 1234567',
                'address' => 'House 42-B, Street 7, Sector F-7/2',
                'city' => 'Islamabad',
                'country' => 'Pakistan',
            ]
        );

        // =========================================================================
        // 2. DEDICATED DEMO COURIER ACCOUNT
        // =========================================================================
        $courierUser = User::updateOrCreate(
            ['email' => 'courier@demo.com'],
            [
                'name' => 'Tariq Rider (Demo Courier Fleet)',
                'username' => 'demo_courier',
                'employee_id' => 'RID-DEMO-01',
                'password' => Hash::make('demo123'),
                'role' => 'courier',
                'phone' => '+92 321 9876543',
                'address' => 'Express Logistics Station, Sector I-9/3',
                'city' => 'Islamabad',
                'is_active' => true,
            ]
        );

        // =========================================================================
        // 3. DEDICATED DEMO ADMIN ACCOUNT
        // =========================================================================
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Khaam Poshak Administrator',
                'username' => 'demo_admin',
                'employee_id' => 'ADM-DEMO-01',
                'password' => Hash::make('demo123'),
                'role' => 'admin',
                'phone' => '+92 333 1122334',
                'address' => 'Khaam Poshak Flagship Store, MM Alam Road, Gulberg III',
                'city' => 'Lahore',
                'is_active' => true,
            ]
        );

        // Also ensure legacy/fallback emails have demo123 password and active role
        User::updateOrCreate(
            ['email' => 'shopper@flexistore.com'],
            [
                'name' => 'Sara Shopper',
                'username' => 'sara_shopper',
                'password' => Hash::make('demo123'),
                'role' => 'customer',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'rider@flexistore.com'],
            [
                'name' => 'Tariq Rider',
                'username' => 'tariq_rider',
                'password' => Hash::make('demo123'),
                'role' => 'delivery',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@flexistore.com'],
            [
                'name' => 'Khaam Poshak Admin',
                'username' => 'admin',
                'password' => Hash::make('demo123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // =========================================================================
        // 4. SAMPLE ORDERS TARGETED TO CUSTOMER & COURIER
        // =========================================================================
        $products = Product::where('store_id', $store->id)->take(4)->get();
        if ($products->count() < 2) {
            $products = Product::take(4)->get();
        }

        $p1 = $products[0] ?? null;
        $p2 = $products[1] ?? null;
        $p3 = $products[2] ?? $p1;
        $p4 = $products[3] ?? $p2;

        // Clean any existing demo orders to avoid duplication
        Order::whereIn('order_number', ['KP-DEMO-1001', 'KP-DEMO-1002', 'KP-DEMO-1003', 'KP-DEMO-1004'])->delete();

        // ORDER 1: Processing (Customer can test "Cancel Order" button!)
        if ($p1) {
            $ord1 = Order::create([
                'store_id' => $store->id,
                'user_id' => $customerUser->id,
                'customer_id' => $customerRecord->id,
                'delivery_user_id' => null,
                'order_number' => 'KP-DEMO-1001',
                'total_amount' => $p1->price,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'final_amount' => $p1->sale_price ?? $p1->price,
                'status' => 'processing',
                'delivery_status' => 'unassigned',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'is_phone_verified' => true,
                'shipping_address' => $customerRecord->address . ', ' . $customerRecord->city,
                'phone' => $customerRecord->phone,
                'notes' => 'Please include luxury gift packaging.',
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ]);

            OrderItem::create([
                'order_id' => $ord1->id,
                'product_id' => $p1->id,
                'product_name' => $p1->name,
                'price' => $p1->sale_price ?? $p1->price,
                'quantity' => 1,
                'selected_attributes' => ['size' => 'M', 'color' => 'Standard'],
                'subtotal' => $p1->sale_price ?? $p1->price,
            ]);
        }

        // ORDER 2: Out for Delivery (Assigned to Demo Courier; Customer sees live tracking, Cancel is disabled)
        if ($p2) {
            $ord2 = Order::create([
                'store_id' => $store->id,
                'user_id' => $customerUser->id,
                'customer_id' => $customerRecord->id,
                'delivery_user_id' => $courierUser->id,
                'order_number' => 'KP-DEMO-1002',
                'total_amount' => $p2->price,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'final_amount' => $p2->sale_price ?? $p2->price,
                'status' => 'shipped',
                'delivery_status' => 'out_for_delivery',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'is_phone_verified' => true,
                'shipping_address' => 'Plot 18, Block C, Phase 5, DHA, Lahore',
                'phone' => '+92 321 9876543',
                'notes' => 'Call upon arrival at the gate.',
                'delivery_notes' => 'Dispatched with Courier Rider Tariq.',
                'created_at' => Carbon::now()->subHours(6),
                'updated_at' => Carbon::now()->subMinutes(30),
            ]);

            OrderItem::create([
                'order_id' => $ord2->id,
                'product_id' => $p2->id,
                'product_name' => $p2->name,
                'price' => $p2->sale_price ?? $p2->price,
                'quantity' => 1,
                'selected_attributes' => ['size' => 'L', 'color' => 'Midnight Navy'],
                'subtotal' => $p2->sale_price ?? $p2->price,
            ]);
        }

        // ORDER 3: Delivered (Completed drop today in Courier earnings & Customer history)
        if ($p3) {
            $ord3 = Order::create([
                'store_id' => $store->id,
                'user_id' => $customerUser->id,
                'customer_id' => $customerRecord->id,
                'delivery_user_id' => $courierUser->id,
                'order_number' => 'KP-DEMO-1003',
                'total_amount' => $p3->price,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'final_amount' => $p3->sale_price ?? $p3->price,
                'status' => 'delivered',
                'delivery_status' => 'delivered',
                'payment_method' => 'cod',
                'payment_status' => 'paid',
                'is_phone_verified' => true,
                'shipping_address' => $customerRecord->address . ', ' . $customerRecord->city,
                'phone' => $customerRecord->phone,
                'notes' => 'Customer confirmed delivery with signed receipt.',
                'delivered_at' => Carbon::today()->addHours(11),
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::today()->addHours(11),
            ]);

            OrderItem::create([
                'order_id' => $ord3->id,
                'product_id' => $p3->id,
                'product_name' => $p3->name,
                'price' => $p3->sale_price ?? $p3->price,
                'quantity' => 1,
                'selected_attributes' => ['size' => 'M', 'color' => 'Crimson'],
                'subtotal' => $p3->sale_price ?? $p3->price,
            ]);
        }

        // ORDER 4: Assigned / Ready for Pickup (Courier can test "Accept / Pick Up" flow)
        if ($p4) {
            $ord4 = Order::create([
                'store_id' => $store->id,
                'user_id' => $customerUser->id,
                'customer_id' => $customerRecord->id,
                'delivery_user_id' => $courierUser->id,
                'order_number' => 'KP-DEMO-1004',
                'total_amount' => $p4->price,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'final_amount' => $p4->sale_price ?? $p4->price,
                'status' => 'processing',
                'delivery_status' => 'assigned',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'is_phone_verified' => true,
                'shipping_address' => 'Apartment 402, Royal Residency, Clifton, Karachi',
                'phone' => '+92 333 5551234',
                'notes' => 'Package at warehouse dispatch bay 3.',
                'created_at' => Carbon::now()->subHours(1),
                'updated_at' => Carbon::now()->subMinutes(15),
            ]);

            OrderItem::create([
                'order_id' => $ord4->id,
                'product_id' => $p4->id,
                'product_name' => $p4->name,
                'price' => $p4->sale_price ?? $p4->price,
                'quantity' => 1,
                'selected_attributes' => ['size' => 'S', 'color' => 'Emerald Green'],
                'subtotal' => $p4->sale_price ?? $p4->price,
            ]);
        }
    }
}
