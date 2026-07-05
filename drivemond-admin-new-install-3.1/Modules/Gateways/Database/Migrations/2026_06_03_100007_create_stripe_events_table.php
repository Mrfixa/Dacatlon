<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Duplicate of database/migrations/2024_01_01_000004_vito_create_stripe_events_table.php,
        // which carries the correct (UUID user_id, nullable amount) schema and always runs first.
        // Guarded so a fresh `migrate --force` doesn't abort on "table already exists".
        if (Schema::hasTable('stripe_events')) {
            return;
        }

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_event_id')->unique();
            $table->string('type', 60);
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('usd');
            $table->string('status', 20)->default('pending');
            $table->string('payment_intent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};
