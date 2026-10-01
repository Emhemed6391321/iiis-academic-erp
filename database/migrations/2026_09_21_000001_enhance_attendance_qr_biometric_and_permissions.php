<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_attendance', function (Blueprint $table) {
            // وسيلة التحقق والتقاط الحركة (QR, البصمة, باركود, يدوي)
            if (!Schema::hasColumn('student_attendance', 'verification_method')) {
                $table->string('verification_method', 30)->default('MANUAL')->after('status');
            }

            // بيانات جهاز البصمة الحيوية أو طرفية القراءة
            if (!Schema::hasColumn('student_attendance', 'device_id')) {
                $table->string('device_id', 100)->nullable()->after('verification_method');
            }
            if (!Schema::hasColumn('student_attendance', 'biometric_log_id')) {
                $table->string('biometric_log_id', 100)->nullable()->after('device_id');
            }

            // تفاصيل إذن الانصراف المبكر الرسمي
            if (!Schema::hasColumn('student_attendance', 'early_permission_slip_number')) {
                $table->string('early_permission_slip_number', 60)->nullable()->after('departure_reason');
            }
            if (!Schema::hasColumn('student_attendance', 'early_permission_reason')) {
                $table->string('early_permission_reason', 150)->nullable()->after('early_permission_slip_number');
            }
            if (!Schema::hasColumn('student_attendance', 'early_permission_guardian_name')) {
                $table->string('early_permission_guardian_name', 150)->nullable()->after('early_permission_reason');
            }
            if (!Schema::hasColumn('student_attendance', 'early_permission_guardian_phone')) {
                $table->string('early_permission_guardian_phone', 50)->nullable()->after('early_permission_guardian_name');
            }
            if (!Schema::hasColumn('student_attendance', 'early_permission_authorized_by')) {
                $table->string('early_permission_authorized_by', 150)->nullable()->after('early_permission_guardian_phone');
            }
            if (!Schema::hasColumn('student_attendance', 'early_permission_notes')) {
                $table->text('early_permission_notes')->nullable()->after('early_permission_authorized_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'verification_method',
                'device_id',
                'biometric_log_id',
                'early_permission_slip_number',
                'early_permission_reason',
                'early_permission_guardian_name',
                'early_permission_guardian_phone',
                'early_permission_authorized_by',
                'early_permission_notes',
            ]);
        });
    }
};
