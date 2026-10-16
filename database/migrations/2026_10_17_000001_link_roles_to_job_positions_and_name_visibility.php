<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * توحيد مسمّى الصفة الوظيفية مع مصفوفة الصلاحيات:
 * - كل دور في مصفوفة الصلاحيات صار له صفة وظيفية معتمدة مرتبطة به (job_positions.role_id)،
 *   ومسماها هو display_name الخاص بالدور، فيظهر المسمى نفسه في المصفوفة وفي الإعدادات المركزية وفي المستندات.
 * - التسكين يحدد هل يظهر اسم الموظف مع الصفة في المستندات الرسمية (show_name_on_documents).
 *
 * الترحيل آمن للتكرار: يتحقق من وجود العمود قبل إضافته ولا يكرر الصفات الموجودة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_positions') && !Schema::hasColumn('job_positions', 'role_id')) {
            Schema::table('job_positions', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->unique()->after('code')->constrained('roles')->nullOnDelete();
            });
        }

        if (Schema::hasTable('employee_placements') && !Schema::hasColumn('employee_placements', 'show_name_on_documents')) {
            Schema::table('employee_placements', function (Blueprint $table) {
                $table->boolean('show_name_on_documents')->default(true)->after('status');
            });
        }

        $now = now();
        foreach (DB::table('roles')->get() as $role) {
            if (DB::table('job_positions')->where('role_id', $role->id)->exists()) {
                continue;
            }

            $code = 'ROLE_' . strtoupper($role->name);
            $existing = DB::table('job_positions')->where('code', $code)->first();

            if ($existing) {
                DB::table('job_positions')->where('id', $existing->id)->update([
                    'role_id'    => $role->id,
                    'title'      => $role->display_name,
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('job_positions')->insert([
                'code'                   => substr($code, 0, 50),
                'role_id'                => $role->id,
                'title'                  => $role->display_name,
                'organizational_unit_id' => null,
                'level_order'            => $role->scope_type === 'GLOBAL_SCOPE' ? 1 : 2,
                'is_active'              => true,
                'description'            => $role->description,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_placements') && Schema::hasColumn('employee_placements', 'show_name_on_documents')) {
            Schema::table('employee_placements', function (Blueprint $table) {
                $table->dropColumn('show_name_on_documents');
            });
        }

        if (Schema::hasTable('job_positions') && Schema::hasColumn('job_positions', 'role_id')) {
            Schema::table('job_positions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }
    }
};
