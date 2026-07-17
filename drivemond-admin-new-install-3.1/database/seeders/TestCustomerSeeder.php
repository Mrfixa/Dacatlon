<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds a single, explicitly-labelled TEST customer so the business owner can log
 * into the customer app immediately after setup, in ANY environment (unlike the
 * demo accounts in DefaultUsersSeeder, which are skipped in production).
 *
 *   Customer app login →  username: "testcustomer"   PIN: "112233"
 *
 * Idempotent (updateOrInsert / existence checks). Change or remove before a real
 * public launch — this account has a known PIN.
 */
class TestCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $levelId = $this->ensureCustomerLevel();
        $userId  = $this->ensureUser('testcustomer', '112233', $levelId, 'Test', 'Customer');
        $this->ensureUserAccount($userId);

        $this->command?->info('TestCustomerSeeder: customer app → username "testcustomer" / PIN "112233".');
    }

    private function ensureCustomerLevel(): ?string
    {
        if (!Schema::hasTable('user_levels')) {
            return null;
        }
        $existing = DB::table('user_levels')->where('user_type', 'customer')->orderBy('sequence')->first();
        return $existing?->id;
    }

    private function ensureUser(string $username, string $pin, ?string $levelId, string $first, string $last): string
    {
        $existing = DB::table('users')->where('username', $username)->first();

        $data = [
            'first_name' => $first,
            'last_name'  => $last,
            'username'   => $username,
            'user_type'  => 'customer',
            'is_active'  => 1,
            'pin_hash'   => Hash::make($pin),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users', 'user_level_id') && $levelId) {
            $data['user_level_id'] = $levelId;
        }
        if (Schema::hasColumn('users', 'pin_attempts')) {
            $data['pin_attempts'] = 0;
        }
        if (Schema::hasColumn('users', 'ref_code')) {
            $data['ref_code'] = $existing->ref_code ?? strtoupper(Str::random(8));
        }

        // Give the test customer the configured test phone number so the phone +
        // predictable-OTP login (config('services.vito_test_otp')) lands directly
        // in this complete-profile account. Only claim the number if it's free.
        $testPhone = trim((string) config('services.vito_test_otp.phone', ''));
        if ($testPhone !== '' && Schema::hasColumn('users', 'phone')) {
            $owner = DB::table('users')->where('phone', $testPhone)->first();
            if (!$owner || ($existing && $owner->id === $existing->id)) {
                $data['phone'] = $testPhone;
            }
        }

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($data);
            return $existing->id;
        }

        $id = (string) Str::uuid();
        DB::table('users')->insert(array_merge($data, [
            'id'         => $id,
            'created_at' => now(),
        ]));

        return $id;
    }

    private function ensureUserAccount(string $userId): void
    {
        if (!Schema::hasTable('user_accounts') || DB::table('user_accounts')->where('user_id', $userId)->exists()) {
            return;
        }
        DB::table('user_accounts')->insert([
            'id'                => (string) Str::uuid(),
            'user_id'           => $userId,
            'payable_balance'   => 0,
            'receivable_balance'=> 0,
            'received_balance'  => 0,
            'pending_balance'   => 0,
            'wallet_balance'    => 0,
            'total_withdrawn'   => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
