<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أُلغي التحقق بخطوتين من النظام بالكامل: تُحذف أعمدته من جدول المستخدمين (السر ورموز الاسترداد وحالة التفعيل).
 * آمنة للتكرار، ولا رجعة فيها لأن الأسرار المحذوفة لا يُعاد بناؤها.
 */
return new class extends Migration
{
    private const COLUMNS = ['two_factor_secret', 'two_factor_enabled', 'two_factor_confirmed_at', 'two_factor_recovery_codes'];

    public function up(): void
    {
        $present = array_values(array_filter(self::COLUMNS, fn ($c) => Schema::hasColumn('users', $c)));

        if ($present !== []) {
            Schema::table('users', function (Blueprint $table) use ($present) {
                $table->dropColumn($present);
            });
        }
    }

    public function down(): void
    {
        // Two-step verification no longer exists; there is nothing to restore.
    }
};