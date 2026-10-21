<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الكوادر التشغيلية المخططة للفرع (عدد الموظفين وعدد المعلمين المعتمد عند الإنشاء).
 * هي أرقام تخطيط تُدخل يدوياً، وتختلف عن الكادر الفعلي الذي يُحسب من الحسابات والتسكينات (BranchStaffService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'operational_admin_staff')) {
                $table->unsignedInteger('operational_admin_staff')->nullable()->after('admin_staff');
            }
            if (!Schema::hasColumn('branches', 'operational_academic_staff')) {
                $table->unsignedInteger('operational_academic_staff')->nullable()->after('operational_admin_staff');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'operational_academic_staff')) {
                $table->dropColumn('operational_academic_staff');
            }
            if (Schema::hasColumn('branches', 'operational_admin_staff')) {
                $table->dropColumn('operational_admin_staff');
            }
        });
    }
};
