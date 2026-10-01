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
        Schema::table('student_grades', function (Blueprint $table) {
            $table->index('student_id', 'idx_student_grades_student_id');
            $table->index('course_id', 'idx_student_grades_course_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index(['branch_id', 'academic_status'], 'idx_students_branch_status');
            $table->index('academic_number', 'idx_students_academic_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropIndex('idx_student_grades_student_id');
            $table->dropIndex('idx_student_grades_course_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_branch_status');
            $table->dropIndex('idx_students_academic_number');
        });
    }
};
