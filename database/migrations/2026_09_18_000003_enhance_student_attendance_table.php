<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendance', function (Blueprint $table) {
            // ربط الحركة بالهيكل الأكاديمي والفرع والعام الدراسي
            if (!Schema::hasColumn('student_attendance', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('student_id')->constrained('branches')->onDelete('cascade');
            }
            if (!Schema::hasColumn('student_attendance', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('branch_id')->constrained('academic_years')->onDelete('cascade');
            }
            if (!Schema::hasColumn('student_attendance', 'study_year_id')) {
                $table->foreignId('study_year_id')->nullable()->after('academic_year_id')->constrained('study_years')->onDelete('set null');
            }
            if (!Schema::hasColumn('student_attendance', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('study_year_id')->constrained('departments')->onDelete('set null');
            }

            // تفاصيل اليوم والأوقات
            if (!Schema::hasColumn('student_attendance', 'day_of_week')) {
                $table->string('day_of_week', 20)->nullable()->after('record_date');
            }
            if (!Schema::hasColumn('student_attendance', 'check_in_time')) {
                $table->time('check_in_time')->nullable()->after('day_of_week');
            }
            if (!Schema::hasColumn('student_attendance', 'check_out_time')) {
                $table->time('check_out_time')->nullable()->after('check_in_time');
            }

            // حالة وتفاصيل الانصراف والتأخير
            if (!Schema::hasColumn('student_attendance', 'departure_status')) {
                $table->enum('departure_status', ['NOT_DEPARTED', 'DEPARTED', 'EARLY_DEPARTURE'])->default('NOT_DEPARTED')->after('check_out_time');
            }
            if (!Schema::hasColumn('student_attendance', 'departure_reason')) {
                $table->text('departure_reason')->nullable()->after('departure_status');
            }
            if (!Schema::hasColumn('student_attendance', 'late_minutes')) {
                $table->integer('late_minutes')->default(0)->after('status');
            }

            // تتبع التعديل والتدقيق
            if (!Schema::hasColumn('student_attendance', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('recorded_by')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('student_attendance', 'modification_reason')) {
                $table->text('modification_reason')->nullable()->after('updated_by');
            }

            // الفهارس الإضافية
            $table->index(['record_date', 'branch_id']);
            $table->index(['record_date', 'study_year_id', 'department_id']);
            $table->index(['academic_year_id', 'student_id']);
            $table->index(['branch_id', 'status']);
        });

        // جدول إشعارات وإنذارات الغياب الرسمية الصادرة للطلاب
        Schema::create('attendance_warning_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('cascade');
            $table->string('notice_number', 50)->unique();
            $table->enum('warning_level', ['FIRST_WARNING', 'SECOND_WARNING', 'FINAL_WARNING', 'EXPULSION_NOTICE'])->default('FIRST_WARNING');
            $table->integer('unexcused_days_count')->default(0);
            $table->integer('total_absence_days')->default(0);
            $table->decimal('absence_percentage', 5, 2)->default(0.00);
            $table->date('notice_date');
            $table->text('admin_statement')->nullable();
            $table->enum('delivery_status', ['PENDING', 'DELIVERED_TO_GUARDIAN', 'SMS_SENT', 'ACKNOWLEDGED'])->default('PENDING');
            $table->date('guardian_contacted_at')->nullable();
            $table->text('guardian_response')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['student_id', 'warning_level']);
            $table->index(['branch_id', 'notice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_warning_notices');
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['study_year_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn([
                'branch_id', 'academic_year_id', 'study_year_id', 'department_id',
                'day_of_week', 'check_in_time', 'check_out_time',
                'departure_status', 'departure_reason', 'late_minutes',
                'updated_by', 'modification_reason'
            ]);
        });
    }
};
