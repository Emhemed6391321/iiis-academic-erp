<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * العمود min_final_exam_score يكتبه CourseController عند إنشاء المقرر وتعديله (قاعدة الـ 40% على الامتحان النهائي)،
 * لكن لم يكن له أي migration: وُجد في قاعدة التطوير بإضافة يدوية وغاب عن الإنتاج، ففشل كل تعديل لمقرر بالخطأ
 * "no such column: min_final_exam_score" (ERR-20261011-C2088، 15 مرة). المهاجرة آمنة التكرار إن كان العمود موجوداً.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'min_final_exam_score')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->decimal('min_final_exam_score', 5, 2)->nullable()->after('second_round_max');
            });
        }

        // تعبئة المقررات القائمة بنفس القاعدة التي يطبقها المتحكم عند غياب القيمة
        DB::table('courses')->whereNull('min_final_exam_score')->update([
            'min_final_exam_score' => DB::raw(
                "CASE WHEN assessment_system = 'ANNUAL_PERIODS_SYSTEM' "
                . "THEN (CASE WHEN weekly_hours = 1 THEN 9.6 ELSE 19.2 END) "
                . "ELSE (CASE WHEN weekly_hours = 1 THEN 11.2 ELSE 22.4 END) END"
            ),
        ]);
    }

    public function down(): void
    {
        // لا يُحذف العمود عمداً: قد يكون موجوداً قبل هذه المهاجرة في قواعد قائمة
    }
};
