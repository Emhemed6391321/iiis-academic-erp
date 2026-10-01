<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('window_type', 50); // REGISTRATION, S1_COURSEWORK, S1_FINAL, S2_FINAL, SECOND_ROUND
            $table->string('title', 150);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('operational_window_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('window_id')->constrained('operational_windows')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('granted_by')->constrained('users')->onDelete('restrict');
            $table->dateTime('extended_until');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_window_exceptions');
        Schema::dropIfExists('operational_windows');
    }
};
