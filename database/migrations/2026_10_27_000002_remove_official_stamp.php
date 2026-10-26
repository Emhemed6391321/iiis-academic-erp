<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * حُذف الختم الرسمي المعتمد من النظام بالكلية: لا رفع، ولا إعداد، ولا طباعة.
 * تزيل الهجرة إعداد الختم المحفوظ وملف صورته، وعمود تفعيل الختم من خانات التوقيع إن وُجد. آمنة للتكرار.
 */
return new class extends Migration
{
    public function up(): void
    {
        $url = DB::table('administrative_settings')->where('key', 'stamp_url')->value('value');

        DB::table('administrative_settings')->where('key', 'stamp_url')->delete();

        if (is_string($url) && str_starts_with($url, '/storage/branding/')) {
            try {
                Storage::disk('public')->delete(substr($url, strlen('/storage/')));
            } catch (\Throwable $e) {
                // A file that cannot be removed does not block the release.
            }
        }

        if (Schema::hasColumn('official_document_signatories', 'show_stamp')) {
            Schema::table('official_document_signatories', function ($table) {
                $table->dropColumn('show_stamp');
            });
        }

        Cache::forget('admin_settings_institute_profile');
        Cache::forget('admin_settings_all_map');
    }

    public function down(): void
    {
        // The stamp feature is gone; nothing to restore.
    }
};
