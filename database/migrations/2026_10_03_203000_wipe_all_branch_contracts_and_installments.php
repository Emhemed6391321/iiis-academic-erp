<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Historical one-off data wipe (contracts and installments), already applied on every
 * existing database. It used to delete rows here, which would destroy real contract data
 * on any environment that ran it later (restore, new server, migrate:fresh + import).
 * Kept as a no-op so the migration history stays consistent. Use
 * `php artisan app:reset-branches` for a deliberate, guarded reset.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Intentionally empty.
    }

    public function down(): void
    {
        // No-op
    }
};
