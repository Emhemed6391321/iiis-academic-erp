<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جداول كانت موجودة على النسخة المحلية دون ملفات ترحيل، فغابت عن الخادم الحي:
 * - أحداث التقويم الأكاديمي (العطلات)
 * - جداول الامتحانات والمواعيد الأكاديمية
 * - المواقع التنظيمية الإدارية
 * الترحيل يتحقق من وجود كل جدول قبل إنشائه.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_calendar_events')) {
            Schema::create('academic_calendar_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_year_id');
                $table->string('title');
                $table->date('event_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_holiday')->default(true);
                $table->string('event_type')->default('عطلة رسمية');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('academic_schedules')) {
            Schema::create('academic_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_year_id');
                $table->string('title');
                $table->string('schedule_type'); // single | extended
                $table->string('round_type');    // first | second
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->text('target_sections')->nullable();
                $table->text('target_levels')->nullable();
                $table->unsignedInteger('duration_days')->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('organizational_positions')) {
            Schema::create('organizational_positions', function (Blueprint $table) {
                $table->id();
                $table->string('admin_code')->unique();
                $table->string('title');
                $table->string('department');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_positions');
        Schema::dropIfExists('academic_schedules');
        Schema::dropIfExists('academic_calendar_events');
    }
};
