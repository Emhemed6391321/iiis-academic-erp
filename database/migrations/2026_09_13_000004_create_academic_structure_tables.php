<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        Schema::create('study_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // السنة الأولى، السنة الثانية، السنة الثالثة
            $table->unsignedTinyInteger('level_order')->unique(); // 1, 2, 3
            $table->string('description', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100); // شُعبة الدراسات الإسلامية
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_year_id')->constrained('study_years')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->unsignedTinyInteger('semester'); // 1 or 2
            $table->string('code', 20);
            $table->string('name', 150);
            $table->unsignedTinyInteger('credit_hours')->default(2);
            $table->decimal('max_coursework_grade', 5, 2)->default(30.00);
            $table->decimal('max_midterm_grade', 5, 2)->default(20.00);
            $table->decimal('max_final_grade', 5, 2)->default(50.00);
            $table->decimal('pass_grade', 5, 2)->default(50.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['study_year_id', 'department_id', 'semester', 'code'], 'uk_course_offering');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('study_years');
        Schema::dropIfExists('academic_years');
    }
};
