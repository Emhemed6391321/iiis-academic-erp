<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * مدير الفروع (branches_director) يشرف على كل الفروع فنطاقه الإدارة العامة، وبقية أدوار الفروع
 * (branch_* ومدرّس المادة) مقيّدة بفرعها. الهجرة تثبّت النطاق في قواعد البيانات التي أُنشئت أدوارها قبل ذلك.
 * آمنة للتكرار.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('name', 'branches_director')->update(['scope_type' => 'GLOBAL_SCOPE']);

        // Matched in PHP: in LIKE the underscore is a wildcard, so 'branch_%' would also catch branches_director.
        $branchRoleIds = DB::table('roles')->pluck('name', 'id')
            ->filter(fn ($name) => str_starts_with((string) $name, 'branch_') || $name === 'course_instructor')
            ->keys();

        DB::table('roles')->whereIn('id', $branchRoleIds)->update(['scope_type' => 'BRANCH_SCOPE']);
    }

    public function down(): void
    {
        // The scopes are a security boundary; they are not reverted.
    }
};
