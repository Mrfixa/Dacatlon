<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mart delivery driver earning. Credited to the driver's wallet when an order is
// marked 'delivered' (VitoMartDriverController::updateStatus). Default 0; the
// earning is the delivery fee + tip, plus an optional commission of the order
// total (mart_driver_commission_percent business setting, default 0).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mart_orders', function (Blueprint $table) {
            $table->decimal('driver_earning', 10, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('mart_orders', function (Blueprint $table) {
            $table->dropColumn('driver_earning');
        });
    }
};
