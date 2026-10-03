<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wipe all contract installments and branch contracts
        if (Schema::hasTable('contract_installments')) {
            DB::table('contract_installments')->delete();
        }

        if (Schema::hasTable('branch_contracts')) {
            DB::table('branch_contracts')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
