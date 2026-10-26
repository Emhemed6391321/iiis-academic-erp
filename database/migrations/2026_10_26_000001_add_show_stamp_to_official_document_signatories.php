<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الختم الرسمي يُربط بخانة التوقيع نفسها: يُطبع عند الخانة المفعّلة التي يُشغَّل فيها مفتاح الختم فقط.
 * الخانات المعتمِدة (approved_by) كانت تطبع الختم قبل هذه الهجرة، فيبقى الوضع كما هو لها.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('official_document_signatories', 'show_stamp')) {
            Schema::table('official_document_signatories', function (Blueprint $table) {
                $table->boolean('show_stamp')->default(false);
            });

            DB::table('official_document_signatories')
                ->where('slot_key', 'approved_by')
                ->update(['show_stamp' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('official_document_signatories', 'show_stamp')) {
            Schema::table('official_document_signatories', function (Blueprint $table) {
                $table->dropColumn('show_stamp');
            });
        }
    }
};
