<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('restrict');
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->unsignedTinyInteger('semester'); // 1 or 2
            $table->enum('exam_type', ['REGULAR', 'SECOND_ROUND'])->default('REGULAR');
            $table->enum('status', [
                'DRAFT',
                'SUBMITTED_TO_HQ',
                'HQ_REJECTED',
                'HQ_APPROVED',
                'FINAL_ARCHIVED'
            ])->default('DRAFT');
            
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            
            $table->text('rejection_notes')->nullable();
            $table->string('batch_digital_hash', 64)->nullable(); // SHA-256
            $table->timestamps();
        });

        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_batch_id')->constrained('grade_batches')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('restrict');
            
            $table->decimal('coursework_grade', 5, 2)->nullable();
            $table->decimal('midterm_grade', 5, 2)->nullable();
            $table->decimal('final_exam_grade', 5, 2)->nullable();
            $table->decimal('final_exam_grade_qr_intisab', 5, 2)->nullable(); // مخصص لطلاب الانتساب
            $table->decimal('second_round_grade', 5, 2)->nullable();
            $table->decimal('total_grade', 5, 2)->nullable();
            
            $table->string('letter_grade', 5)->nullable();
            $table->enum('status', ['PASS', 'FAIL', 'RESIT', 'ABSENT', 'EXEMPTED'])->default('FAIL');
            $table->boolean('is_locked')->default(false);
            $table->dateTime('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['grade_batch_id', 'student_id', 'course_id'], 'uk_student_grade_record');
        });

        Schema::create('grade_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_grade_id')->constrained('student_grades')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            
            $table->string('modified_field', 80);
            $table->string('old_value', 50)->nullable();
            $table->string('new_value', 50)->nullable();
            
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['branch_id', 'student_id', 'course_id']);
        });

        Schema::create('system_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('event_type', 50);
            $table->text('description');
            $table->string('ip_address', 45);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_audit_trails');
        Schema::dropIfExists('grade_logs');
        Schema::dropIfExists('student_grades');
        Schema::dropIfExists('grade_batches');
    }
};
