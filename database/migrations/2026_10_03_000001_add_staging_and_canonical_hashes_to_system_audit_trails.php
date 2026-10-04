<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_audit_trails', function (Blueprint $table) {
            $table->string('staging_offline_hash', 64)->nullable()->index()->after('record_hash');
            $table->string('canonical_ledger_hash', 64)->nullable()->index()->after('staging_offline_hash');
            $table->boolean('is_offline_staged')->default(false)->index()->after('canonical_ledger_hash');
            $table->dateTime('staged_at')->nullable()->after('is_offline_staged');
            $table->string('client_uuid', 64)->nullable()->index()->after('staged_at');
        });

        // Initialize canonical_ledger_hash for existing records
        \Illuminate\Support\Facades\DB::statement('UPDATE system_audit_trails SET canonical_ledger_hash = record_hash WHERE record_hash IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('system_audit_trails', function (Blueprint $table) {
            $table->dropColumn([
                'staging_offline_hash',
                'canonical_ledger_hash',
                'is_offline_staged',
                'staged_at',
                'client_uuid',
            ]);
        });
    }
};
