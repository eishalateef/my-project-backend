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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('payment_method'); // pending, paid, failed, refunded
            $table->string('payment_gateway')->nullable()->after('payment_status'); // cod, jazzcash, easypaisa, payfast
            $table->string('transaction_id')->nullable()->index()->after('payment_gateway');
            $table->json('payment_details')->nullable()->after('transaction_id');
            $table->timestamp('paid_at')->nullable()->after('payment_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_gateway',
                'transaction_id',
                'payment_details',
                'paid_at',
            ]);
        });
    }
};
