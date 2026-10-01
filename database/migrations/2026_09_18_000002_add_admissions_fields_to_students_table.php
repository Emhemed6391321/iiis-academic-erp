<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'ministry_student_id')) {
                $table->string('ministry_student_id')->nullable()->after('academic_number');
            }
            if (!Schema::hasColumn('students', 'digital_signature_path')) {
                $table->text('digital_signature_path')->nullable()->after('profile_photo_path');
            }
            if (!Schema::hasColumn('students', 'has_disability')) {
                $table->boolean('has_disability')->default(false)->after('is_special_needs');
            }
            if (!Schema::hasColumn('students', 'disability_type')) {
                $table->string('disability_type')->nullable()->after('has_disability');
            }
            if (!Schema::hasColumn('students', 'disability_details')) {
                $table->text('disability_details')->nullable()->after('disability_type');
            }
            if (!Schema::hasColumn('students', 'chronic_diseases_list')) {
                $table->text('chronic_diseases_list')->nullable()->after('chronic_diseases');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'ministry_student_id',
                'digital_signature_path',
                'has_disability',
                'disability_type',
                'disability_details',
                'chronic_diseases_list',
            ]);
        });
    }
};
