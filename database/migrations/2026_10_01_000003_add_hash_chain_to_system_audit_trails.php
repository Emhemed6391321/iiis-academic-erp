<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_audit_trails', function (Blueprint $table) {
            $table->string('previous_hash', 64)->nullable()->after('payload');
            $table->string('record_hash', 64)->nullable()->index()->after('previous_hash');
        });
    }

    public function down(): void
    {
        Schema::table('system_audit_trails', function (Blueprint $table) {
            $table->dropColumn(['previous_hash', 'record_hash']);
        });
    }
};
