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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('order_number')->nullable()->index();
            $table->string('gateway'); // jazzcash, easypaisa, payfast, cod
            $table->string('transaction_id')->unique()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('PKR');
            $table->string('account_number')->nullable();
            $table->string('status')->default('pending'); // pending, successful, failed, refunded
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
