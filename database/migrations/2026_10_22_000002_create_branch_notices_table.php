<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إشعارات الفروع المحفوظة: نتيجة قرار الإدارة العامة على ملف يخص الفرع.
 * لا يُربط الإشعار بالطالب بمفتاح أجنبي، لأن ملف الطالب المرفوض يُحذف نهائياً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('title', 150);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_notices');
    }
};
