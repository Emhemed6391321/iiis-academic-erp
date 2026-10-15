<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجلات التشخيص التي تُلتقط تلقائياً مع البلاغ: سجل وحدة التحكم، أخطاء JavaScript،
 * الطلبات الفاشلة إلى الخادم، وسياق الصفحة والمتصفح — حتى يُصلح الخطأ من البلاغ نفسه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('bug_reports', 'diagnostics')) {
                $table->json('diagnostics')->nullable()->after('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            if (Schema::hasColumn('bug_reports', 'diagnostics')) {
                $table->dropColumn('diagnostics');
            }
        });
    }
};
