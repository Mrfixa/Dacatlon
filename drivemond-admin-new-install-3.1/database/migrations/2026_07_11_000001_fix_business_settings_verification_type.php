<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Heal already-deployed installs where customer_verification / driver_verification were seeded
 * under settings_type='business_settings' but every reader/writer looks them up under
 * 'business_information' (LoginSettingsController, BusinessSettingService, AuthController). The
 * mismatch made businessConfig() return null → the customer verification/registration flow threw.
 *
 * Idempotent: if a correctly-typed row already exists we drop the stray one; otherwise we re-point
 * the stray row's settings_type. Safe to run on both fresh (nothing to fix) and legacy databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        foreach (['customer_verification', 'driver_verification'] as $key) {
            $correct = DB::table('business_settings')
                ->where('key_name', $key)
                ->where('settings_type', 'business_information')
                ->exists();

            if ($correct) {
                // A properly-typed row already exists; delete any stray business_settings copy.
                DB::table('business_settings')
                    ->where('key_name', $key)
                    ->where('settings_type', 'business_settings')
                    ->delete();
            } else {
                // Re-point the stray row so lookups by 'business_information' find it.
                DB::table('business_settings')
                    ->where('key_name', $key)
                    ->where('settings_type', 'business_settings')
                    ->update(['settings_type' => 'business_information']);
            }
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: reverting would re-introduce the crashing mismatch.
    }
};
