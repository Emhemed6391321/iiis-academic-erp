<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إضافة حقل academic_year_id إلى جدول courses
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'academic_year_id')) {
                $table->foreignId('academic_year_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('academic_years')
                      ->onDelete('cascade');
            }

            if (!Schema::hasColumn('courses', 'max_score')) {
                $table->decimal('max_score', 5, 2)->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('courses', 'pass_min_score')) {
                $table->decimal('pass_min_score', 5, 2)->nullable()->after('max_score');
            }
            if (!Schema::hasColumn('courses', 'second_round_max')) {
                $table->decimal('second_round_max', 5, 2)->nullable()->after('pass_min_score');
            }
            if (!Schema::hasColumn('courses', 'assessment_system')) {
                $table->string('assessment_system', 50)->nullable()->after('second_round_max');
            }
            if (!Schema::hasColumn('courses', 'weekly_hours')) {
                $table->unsignedTinyInteger('weekly_hours')->default(2)->after('credit_hours');
            }
        });

        // 2. ربط المقررات الموجودة بالعام الدراسي النشط الحالي
        $currentYear = DB::table('academic_years')->where('is_current', true)->first();
        if ($currentYear) {
            DB::table('courses')->whereNull('academic_year_id')->update([
                'academic_year_id' => $currentYear->id,
            ]);
        } else {
            // إذا لم يكن هناك عام نشط، اربط بأول عام موجود
            $firstYear = DB::table('academic_years')->orderBy('id')->first();
            if ($firstYear) {
                DB::table('courses')->whereNull('academic_year_id')->update([
                    'academic_year_id' => $firstYear->id,
                ]);
            }
        }

        // 3. إضافة unique constraint جديدة تشمل academic_year_id
        // أولاً نحذف الـ constraint القديمة إذا كانت موجودة
        try {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropUnique('uk_course_offering');
            });
        } catch (\Exception $e) {
            // الـ constraint غير موجودة، نكمل
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->unique(
                ['academic_year_id', 'study_year_id', 'department_id', 'semester', 'code'],
                'uk_course_year_offering'
            );
        });

        // 4. إضافة عمود status للعام الدراسي لتتبع مرحلته
        Schema::table('academic_years', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_years', 'status')) {
                $table->enum('status', ['DRAFT', 'ACTIVE', 'LOCKED', 'ARCHIVED'])
                      ->default('DRAFT')
                      ->after('is_locked');
            }
            if (!Schema::hasColumn('academic_years', 'activated_by')) {
                $table->unsignedBigInteger('activated_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('academic_years', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('activated_by');
            }
            if (!Schema::hasColumn('academic_years', 'locked_by')) {
                $table->unsignedBigInteger('locked_by')->nullable()->after('activated_at');
            }
            if (!Schema::hasColumn('academic_years', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('academic_years', 'notes')) {
                $table->text('notes')->nullable()->after('locked_at');
            }
        });

        // 5. تحديث status للأعوام الموجودة بناءً على is_current و is_locked
        DB::table('academic_years')->where('is_locked', true)->update(['status' => 'LOCKED']);
        DB::table('academic_years')->where('is_current', true)->where('is_locked', false)->update(['status' => 'ACTIVE']);
        DB::table('academic_years')->where('is_current', false)->where('is_locked', false)->update(['status' => 'DRAFT']);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            try { $table->dropUnique('uk_course_year_offering'); } catch (\Exception $e) {}
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn(['academic_year_id', 'max_score', 'pass_min_score', 'second_round_max', 'assessment_system', 'weekly_hours']);
        });

        Schema::table('academic_years', function (Blueprint $table) {
            $cols = ['status', 'activated_by', 'activated_at', 'locked_by', 'locked_at', 'notes'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('academic_years', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
