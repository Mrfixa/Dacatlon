<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // trip_requests ships with bare foreignUuid columns (no constrained(), so no
        // indexes). Hot paths: driver pending lists filter current_status + zone_id;
        // customer/driver histories filter their id column.
        if (Schema::hasTable('trip_requests')) {
            Schema::table('trip_requests', function (Blueprint $table) {
                try { $table->index(['current_status', 'zone_id'], 'trip_requests_status_zone_index'); } catch (\Exception $e) {}
                try { $table->index('customer_id', 'trip_requests_customer_id_index'); } catch (\Exception $e) {}
                try { $table->index('driver_id', 'trip_requests_driver_id_index'); } catch (\Exception $e) {}
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trip_requests')) {
            Schema::table('trip_requests', function (Blueprint $table) {
                try { $table->dropIndex('trip_requests_status_zone_index'); } catch (\Exception $e) {}
                try { $table->dropIndex('trip_requests_customer_id_index'); } catch (\Exception $e) {}
                try { $table->dropIndex('trip_requests_driver_id_index'); } catch (\Exception $e) {}
            });
        }
    }
};
