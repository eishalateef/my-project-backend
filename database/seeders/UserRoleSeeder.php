<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin Account (Pakistan & Global)
        $admin = User::updateOrCreate(
            ['email' => 'admin@flexistore.pk'],
            [
                'name' => 'FlexiStore Pakistan Admin',
                'username' => 'admin_pk',
                'employee_id' => 'ADM-PK01',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => '+92-300-1110000',
                'address' => 'Floor 7, Arfa Software Technology Park, Ferozepur Road',
                'city' => 'Lahore',
            ]
        );

        // Keep standard admin account
        User::updateOrCreate(
            ['email' => 'admin@flexistore.com'],
            [
                'name' => 'FlexiStore Master Admin',
                'username' => 'admin',
                'employee_id' => 'ADM-001',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => '+92-300-1234567',
                'address' => 'Main Boulevard, Gulberg III',
                'city' => 'Lahore',
            ]
        );

        // 2. Pakistani Shoppers / Customers
        $shopper1 = User::updateOrCreate(
            ['email' => 'fatima@flexistore.pk'],
            [
                'name' => 'Fatima Tariq (Lahore Shopper)',
                'username' => 'fatima_shopper',
                'employee_id' => null,
                'password' => Hash::make('shopper123'),
                'role' => 'customer',
                'is_active' => true,
                'phone' => '+92-301-8472910',
                'address' => 'Plot 84, Sector J, Phase 5, DHA',
                'city' => 'Lahore',
            ]
        );

        $shopper2 = User::updateOrCreate(
            ['email' => 'bilal@flexistore.pk'],
            [
                'name' => 'Bilal Hassan (Islamabad Shopper)',
                'username' => 'bilal_shopper',
                'employee_id' => null,
                'password' => Hash::make('shopper123'),
                'role' => 'customer',
                'is_active' => true,
                'phone' => '+92-321-4920184',
                'address' => 'Street 8, Sector F-7/2',
                'city' => 'Islamabad',
            ]
        );

        $shopper3 = User::updateOrCreate(
            ['email' => 'shopper@flexistore.com'],
            [
                'name' => 'Sara Al-Maktoum (Shopper)',
                'username' => 'sara_shopper',
                'employee_id' => null,
                'password' => Hash::make('shopper123'),
                'role' => 'customer',
                'is_active' => true,
                'phone' => '+92-333-9182736',
                'address' => 'House 14-C, Khayaban-e-Mujahid, DHA Phase 5',
                'city' => 'Karachi',
            ]
        );

        // 3. Pakistani Delivery Couriers & Riders
        $rider1 = User::updateOrCreate(
            ['email' => 'usman.tcs@flexistore.pk'],
            [
                'name' => 'Muhammad Usman (TCS Express Courier)',
                'username' => 'usman_rider',
                'employee_id' => 'RDR-PK01',
                'password' => Hash::make('rider123'),
                'role' => 'delivery',
                'is_active' => true,
                'phone' => '+92-300-1122334',
                'address' => 'TCS Regional Hub, Airport Road, Cantt',
                'city' => 'Lahore',
            ]
        );

        $rider2 = User::updateOrCreate(
            ['email' => 'shahid.leopards@flexistore.pk'],
            [
                'name' => 'Shahid Afridi (Leopards Courier)',
                'username' => 'shahid_rider',
                'employee_id' => 'RDR-PK02',
                'password' => Hash::make('rider123'),
                'role' => 'delivery',
                'is_active' => true,
                'phone' => '+92-321-4455667',
                'address' => 'Leopards Hub, Shahr-e-Faisal',
                'city' => 'Karachi',
            ]
        );

        $rider3 = User::updateOrCreate(
            ['email' => 'rizwan.trax@flexistore.pk'],
            [
                'name' => 'Muhammad Rizwan (Trax Express)',
                'username' => 'rizwan_rider',
                'employee_id' => 'RDR-PK03',
                'password' => Hash::make('rider123'),
                'role' => 'delivery',
                'is_active' => true,
                'phone' => '+92-333-7788990',
                'address' => 'Trax Distribution Station, I-9 Industrial Area',
                'city' => 'Islamabad',
            ]
        );

        // Also keep legacy demo accounts for quick logins
        User::updateOrCreate(
            ['email' => 'rider@flexistore.com'],
            [
                'name' => 'Tariq Express (Delivery Courier #08)',
                'username' => 'tariq_rider',
                'employee_id' => 'RDR-008',
                'password' => Hash::make('rider123'),
                'role' => 'delivery',
                'is_active' => true,
                'phone' => '+92-302-5544332',
                'address' => 'Gulberg Dispatch Center',
                'city' => 'Lahore',
            ]
        );

        // Distribute existing orders among riders and shoppers
        $orders = Order::all();
        $riderPool = [$rider1, $rider2, $rider3];
        $shopperPool = [$shopper1, $shopper2, $shopper3];

        if ($orders->count() > 0) {
            foreach ($orders as $index => $order) {
                if (!$order->user_id) {
                    $order->user_id = $shopperPool[$index % count($shopperPool)]->id;
                }
                if (!$order->delivery_user_id) {
                    $order->delivery_user_id = $riderPool[$index % count($riderPool)]->id;
                    $order->delivery_status = ($index % 2 === 0) ? 'out_for_delivery' : 'assigned';
                }
                $order->save();
            }
        }
    }
}
