<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول ملاحظات السجل
        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->text('note_text');
            $table->text('reply_text')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('replied_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->index('student_id');
        });

        // جدول المخالفات السلوكية
        Schema::create('student_behaviors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('violation_type', 150);
            $table->enum('warning_level', ['LEVEL_1', 'LEVEL_2', 'LEVEL_3'])->default('LEVEL_1');
            $table->text('description')->nullable();
            $table->text('action_taken')->nullable();
            $table->date('violation_date')->useCurrent();
            $table->foreignId('logged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['student_id', 'warning_level']);
        });

        // جدول طلبات الأعذار
        Schema::create('excuse_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('attachment_path', 255)->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });

        // جدول تاريخ تغيير حالة الطالب
        Schema::create('student_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->string('event_type', 50)->default('STATUS_CHANGE');
            // event_type: STATUS_CHANGE | RENEWAL | PAUSE | TRANSFER | PROMOTION | SYSTEM_CHANGE | WITHDRAWAL | DEPT_CHANGE
            $table->text('reason')->nullable();
            $table->string('document_path', 255)->nullable();
            $table->json('meta')->nullable(); // بيانات إضافية حسب نوع الحدث
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('event_date')->useCurrent();
            $table->timestamps();
            $table->index(['student_id', 'event_type']);
        });

        // جدول طلبات إيقاف/تجديد القيد
        Schema::create('enrollment_status_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('request_type', ['PAUSE', 'RENEWAL'])->comment('PAUSE=إيقاف, RENEWAL=تجديد');
            $table->foreignId('target_academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->text('reason');
            $table->string('document_path', 255)->nullable();
            $table->enum('branch_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->enum('hq_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->enum('final_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('rejection_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('branch_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('hq_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['student_id', 'final_status']);
        });

        // جدول طلبات تغيير صفة القيد (نظامي/انتساب)
        Schema::create('study_type_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('old_type', ['REGULAR', 'INTISAB']);
            $table->enum('new_type', ['REGULAR', 'INTISAB']);
            $table->text('reason');
            $table->boolean('is_full_absence')->default(false);
            $table->string('document_path', 255)->nullable();
            $table->enum('branch_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->enum('hq_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->enum('board_status', ['NOT_REQUIRED', 'PENDING', 'APPROVED', 'REJECTED'])->default('NOT_REQUIRED');
            $table->enum('final_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['student_id', 'final_status']);
        });

        // جدول سجل الحضور اليومي للطالب
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('record_date');
            $table->enum('status', ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED'])->default('PRESENT');
            $table->text('absence_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['student_id', 'record_date']);
            $table->index(['student_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('study_type_change_requests');
        Schema::dropIfExists('enrollment_status_requests');
        Schema::dropIfExists('student_status_history');
        Schema::dropIfExists('excuse_requests');
        Schema::dropIfExists('student_behaviors');
        Schema::dropIfExists('student_notes');
    }
};
