<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('email'); // 'admin', 'customer', 'delivery'
            $table->string('phone')->nullable()->after('role');
            $table->text('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('delivery_status')->default('unassigned')->after('status'); // 'unassigned', 'assigned', 'out_for_delivery', 'delivered', 'failed'
            $table->text('delivery_notes')->nullable()->after('notes');
            $table->timestamp('delivered_at')->nullable()->after('delivery_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['delivery_user_id']);
            $table->dropColumn(['user_id', 'delivery_user_id', 'delivery_status', 'delivery_notes', 'delivered_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'address', 'city']);
        });
    }
};
