<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_year_rollover_logs')) {
            Schema::create('academic_year_rollover_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_academic_year_id')->constrained('academic_years');
                $table->foreignId('to_academic_year_id')->constrained('academic_years');
                $table->foreignId('executed_by')->nullable()->constrained('users');
                $table->integer('students_evaluated')->default(0);
                $table->integer('students_promoted')->default(0);
                $table->integer('students_held_back')->default(0);
                $table->integer('students_graduated')->default(0);
                $table->integer('students_second_round')->default(0);
                $table->integer('courses_copied')->default(0);
                $table->json('summary_json')->nullable();
                $table->string('status', 50)->default('COMPLETED');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_year_rollover_logs');
    }
};
