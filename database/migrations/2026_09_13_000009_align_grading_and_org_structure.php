<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekly_hours')->default(2)->after('credit_hours');
            $table->string('assessment_system', 40)->default('SEMESTER_SYSTEM')->after('weekly_hours'); // SEMESTER_SYSTEM or ANNUAL_PERIODS_SYSTEM
            $table->decimal('max_score', 5, 2)->default(80.00)->after('pass_grade');
            $table->decimal('pass_min_score', 5, 2)->default(40.00)->after('max_score');
            $table->decimal('second_round_max', 5, 2)->default(56.00)->after('pass_min_score');
        });

        Schema::table('student_grades', function (Blueprint $table) {
            // Semester System Detailed Breakdown (Years 1 & 2)
            $table->decimal('daily_activities', 5, 2)->nullable()->after('course_id');
            $table->decimal('applications_avg', 5, 2)->nullable()->after('daily_activities');
            $table->decimal('semester_work_total', 5, 2)->nullable()->after('midterm_grade');
            $table->decimal('semester_final_exam', 5, 2)->nullable()->after('semester_work_total');
            $table->decimal('semester_total', 5, 2)->nullable()->after('semester_final_exam');
            $table->decimal('second_semester_final_exam', 5, 2)->nullable()->after('semester_total');
            $table->decimal('both_semesters_final_total', 5, 2)->nullable()->after('second_semester_final_exam');
            $table->decimal('both_semesters_grand_total', 5, 2)->nullable()->after('both_semesters_final_total');

            // Annual Periods Breakdown (Year 3 - Graduation Year)
            $table->decimal('period1_activities', 5, 2)->nullable()->after('both_semesters_grand_total');
            $table->decimal('period1_written', 5, 2)->nullable()->after('period1_activities');
            $table->decimal('period1_exam', 5, 2)->nullable()->after('period1_written');
            $table->decimal('period1_total', 5, 2)->nullable()->after('period1_exam');
            $table->decimal('period2_activities', 5, 2)->nullable()->after('period1_total');
            $table->decimal('period2_written', 5, 2)->nullable()->after('period2_activities');
            $table->decimal('period2_exam', 5, 2)->nullable()->after('period2_written');
            $table->decimal('period2_total', 5, 2)->nullable()->after('period2_exam');
            $table->decimal('periods_combined_total', 5, 2)->nullable()->after('period2_total');
            $table->decimal('year_end_exam', 5, 2)->nullable()->after('periods_combined_total');

            // Validation Engine Flags & Grand Totals
            $table->decimal('final_grand_total', 5, 2)->nullable()->after('year_end_exam');
            $table->boolean('passed_exam_rule')->default(false)->after('final_grand_total');
            $table->boolean('passed_total_rule')->default(false)->after('passed_exam_rule');
            $table->string('academic_status_note', 100)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropColumn([
                'daily_activities',
                'applications_avg',
                'semester_work_total',
                'semester_final_exam',
                'semester_total',
                'second_semester_final_exam',
                'both_semesters_final_total',
                'both_semesters_grand_total',
                'period1_activities',
                'period1_written',
                'period1_exam',
                'period1_total',
                'period2_activities',
                'period2_written',
                'period2_exam',
                'period2_total',
                'periods_combined_total',
                'year_end_exam',
                'final_grand_total',
                'passed_exam_rule',
                'passed_total_rule',
                'academic_status_note',
            ]);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'weekly_hours',
                'assessment_system',
                'max_score',
                'pass_min_score',
                'second_round_max',
            ]);
        });
    }
};
