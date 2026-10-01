<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // صورة شخصية
            $table->string('profile_photo_path', 255)->nullable()->after('notes');
            // بيانات صحية وخاصة
            $table->boolean('is_special_needs')->default(false)->after('profile_photo_path');
            $table->text('special_needs_desc')->nullable()->after('is_special_needs');
            $table->string('medical_report_path', 255)->nullable()->after('special_needs_desc');
            // توثيق البيانات
            $table->foreignId('data_verified_by')->nullable()->constrained('users')->onDelete('set null')->after('medical_report_path');
            $table->timestamp('data_verified_at')->nullable()->after('data_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['data_verified_by']);
            $table->dropColumn([
                'profile_photo_path',
                'is_special_needs',
                'special_needs_desc',
                'medical_report_path',
                'data_verified_by',
                'data_verified_at',
            ]);
        });
    }
};
