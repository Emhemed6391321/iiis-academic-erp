<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('academic_number', 30)->nullable()->unique();
            $table->string('national_id', 20)->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('current_study_year_id')->constrained('study_years')->onDelete('restrict');
            
            $table->string('first_name', 50);
            $table->string('father_name', 50);
            $table->string('grandfather_name', 50);
            $table->string('family_name', 50);
            $table->string('mother_name', 100);
            
            $table->enum('gender', ['MALE', 'FEMALE']);
            $table->date('birth_date');
            $table->string('birth_place', 100);
            $table->string('nationality', 50)->default('ليبي');
            
            $table->string('phone', 30);
            $table->string('guardian_phone', 30);
            
            $table->enum('study_type', ['REGULAR', 'INTISAB'])->default('REGULAR');
            $table->enum('academic_status', [
                'NEW_DRAFT',
                'PENDING_HQ',
                'REJECTED_REVISION',
                'ENROLLED_ACTIVE',
                'SUSPENDED',
                'TRANSFERRED',
                'GRADUATED',
                'EXPELLED'
            ])->default('NEW_DRAFT');
            
            $table->foreignId('enrolled_academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'current_study_year_id', 'academic_status']);
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('document_type', [
                'BASIC_EDUCATION_CERT',
                'BIRTH_CERT',
                'NATIONAL_ID_CARD',
                'PERSONAL_PHOTO',
                'OTHER'
            ]);
            $table->string('file_path', 255);
            $table->string('file_hash', 64);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('from_branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('to_branch_id')->constrained('branches')->onDelete('restrict');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('students');
    }
};
