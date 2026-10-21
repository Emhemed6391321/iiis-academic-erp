<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * قائمة الجنسيات الأجنبية التي يختار منها مسجل الفرع عند قيد الطالب غير الليبي.
 * «ليبي» ثابتة في النظام ولا تُدار من هذه القائمة. تُزرع القائمة الافتراضية هنا
 * حتى تصل إلى الخادم مع الهجرة نفسها.
 */
return new class extends Migration
{
    private const DEFAULT_NATIONALITIES = [
        'تونسي',
        'جزائري',
        'مغربي',
        'مصري',
        'سوداني',
        'تشادي',
        'نيجري',
        'موريتاني',
        'مالي',
        'نيجيري',
        'صومالي',
        'سوري',
        'فلسطيني',
        'يمني',
        'عراقي',
        'أردني',
        'بنغلاديشي',
        'باكستاني',
        'إندونيسي',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('nationalities')) {
            Schema::create('nationalities', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();
        foreach (self::DEFAULT_NATIONALITIES as $index => $name) {
            DB::table('nationalities')->insertOrIgnore([
                'name' => $name,
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
