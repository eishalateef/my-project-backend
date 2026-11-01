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
            if (!Schema::hasColumn('orders', 'delivery_type')) {
                $table->string('delivery_type')->nullable()->after('delivery_status'); // 'own_rider', 'third_party'
            }
            if (!Schema::hasColumn('orders', 'assigned_to')) {
                $table->string('assigned_to')->nullable()->after('delivery_type'); // Courier user ID / name or third-party service ('Leopards', 'TCS', 'Trax')
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('orders', 'delivery_type')) {
                $cols[] = 'delivery_type';
            }
            if (Schema::hasColumn('orders', 'assigned_to')) {
                $cols[] = 'assigned_to';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
