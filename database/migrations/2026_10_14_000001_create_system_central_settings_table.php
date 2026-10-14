<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول الإعدادات المركزية (أيام العطلة، إعدادات الخدمات، بوابات الفترات).
 * كان موجوداً على النسخة المحلية دون ملف ترحيل، فغاب عن الخادم الحي وتسبب في أخطاء
 * "no such table" عند فتح الإعدادات الإدارية. الترحيل آمن للتشغيل مرة أخرى لأنه يتحقق من وجود الجدول.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_central_settings')) {
            return;
        }

        Schema::create('system_central_settings', function (Blueprint $table) {
            $table->string('setting_key')->primary();
            $table->text('setting_value');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_central_settings');
    }
};
