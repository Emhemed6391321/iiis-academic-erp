<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * كل خانة توقيع تتحكم في ظهور اسم الموقّع وصفته في المستندات، من مسميات التوقيعات المركزية وحدها.
 * كما يُصحَّح الاسم الرسمي للمعهد المخزّن في الإعدادات والمسميات.
 */
return new class extends Migration
{
    private const OLD_NAME = 'المعهد المتوسط للدراسات الإسلامية';
    private const NEW_NAME = 'المعهد التخصصي للدراسات الإسلامية';

    public function up(): void
    {
        Schema::table('official_document_signatories', function (Blueprint $table) {
            if (!Schema::hasColumn('official_document_signatories', 'show_name')) {
                $table->boolean('show_name')->default(true);
            }
            if (!Schema::hasColumn('official_document_signatories', 'show_title')) {
                $table->boolean('show_title')->default(true);
            }
        });

        // Only the text is replaced; the rows and their links stay as they are.
        $replace = "REPLACE(%s, '" . self::OLD_NAME . "', '" . self::NEW_NAME . "')";

        DB::table('administrative_settings')
            ->where('value', 'like', '%' . self::OLD_NAME . '%')
            ->update(['value' => DB::raw(sprintf($replace, 'value'))]);

        foreach (['slot_label', 'custom_title_override'] as $column) {
            DB::table('official_document_signatories')
                ->where($column, 'like', '%' . self::OLD_NAME . '%')
                ->update([$column => DB::raw(sprintf($replace, $column))]);
        }
    }

    public function down(): void
    {
        Schema::table('official_document_signatories', function (Blueprint $table) {
            if (Schema::hasColumn('official_document_signatories', 'show_title')) {
                $table->dropColumn('show_title');
            }
            if (Schema::hasColumn('official_document_signatories', 'show_name')) {
                $table->dropColumn('show_name');
            }
        });
    }
};
