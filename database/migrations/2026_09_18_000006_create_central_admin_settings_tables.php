<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Central Administrative Settings
        if (!Schema::hasTable('administrative_settings')) {
            Schema::create('administrative_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_group')->default('general')->index();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->default('string'); // string, json, image, boolean, integer
                $table->string('label')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 2. Organizational Units (Hierarchy Tree)
        if (!Schema::hasTable('organizational_units')) {
            Schema::create('organizational_units', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('type')->default('department'); // general_admin, department, section, unit, committee, office
                $table->foreignId('parent_id')->nullable()->constrained('organizational_units')->nullOnDelete();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Official Job Positions (الصفات والمسميات الوظيفية)
        if (!Schema::hasTable('job_positions')) {
            Schema::create('job_positions', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('title');
                $table->foreignId('organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
                $table->integer('level_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 4. Employee Placements (تسكين الموظفين)
        if (!Schema::hasTable('employee_placements')) {
            Schema::create('employee_placements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('job_position_id')->constrained('job_positions')->cascadeOnDelete();
                $table->foreignId('organizational_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(true);
                $table->string('status')->default('active'); // active, historic, transferred
                $table->string('decision_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Official Document Signatories (ربط مخرجات النظام والتقارير بالمسميات المركزية)
        if (!Schema::hasTable('official_document_signatories')) {
            Schema::create('official_document_signatories', function (Blueprint $table) {
                $table->id();
                $table->string('document_code'); // attendance_sheet, enrollment_cert, conduct_cert, secret_report, transcript, warning_notice
                $table->string('slot_key');      // prepared_by, verified_by, approved_by
                $table->string('slot_label');    // إعداد, مراجعة وتدقيق, اعتماد
                $table->foreignId('job_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('custom_title_override')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['document_code', 'slot_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('official_document_signatories');
        Schema::dropIfExists('employee_placements');
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('organizational_units');
        Schema::dropIfExists('administrative_settings');
    }
};
