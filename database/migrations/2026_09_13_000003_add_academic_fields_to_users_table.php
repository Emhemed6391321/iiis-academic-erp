<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->onDelete('set null');
            $table->foreignId('role_id')->nullable()->after('branch_id')->constrained('roles')->onDelete('restrict');
            $table->string('national_id', 20)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->text('two_factor_secret')->nullable()->after('is_active');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'branch_id',
                'role_id',
                'national_id',
                'phone',
                'is_active',
                'two_factor_secret',
                'two_factor_enabled',
                'last_login_at',
                'last_login_ip'
            ]);
        });
    }
};
