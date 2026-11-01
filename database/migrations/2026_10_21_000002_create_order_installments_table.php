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
        // 1. Add Installment Tracking Fields to Orders Table
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_installment')->default(false)->after('payment_method');
            $table->string('customer_cnic', 30)->nullable()->after('is_installment');
            $table->string('alternate_phone', 30)->nullable()->after('customer_cnic');
            $table->string('installment_status', 30)->default('none')->after('alternate_phone'); // none, active, completed, overdue, defaulted
            $table->unsignedTinyInteger('total_installments')->default(4)->after('installment_status');
            $table->unsignedTinyInteger('installments_paid')->default(0)->after('total_installments');
        });

        // 2. Create Order Installments Ledger Table
        Schema::create('order_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('order_number')->index();
            $table->unsignedTinyInteger('installment_number'); // 1, 2, 3, 4
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 30)->default('pending'); // paid, pending, overdue
            $table->string('payment_method', 50)->nullable(); // jazzcash, easypaisa, card, cash, bank_transfer, flexipay
            $table->string('transaction_ref', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'installment_number']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_installments');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_installment',
                'customer_cnic',
                'alternate_phone',
                'installment_status',
                'total_installments',
                'installments_paid',
            ]);
        });
    }
};
