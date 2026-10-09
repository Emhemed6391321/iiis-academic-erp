<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Extra safety for commands that wipe data. Outside production the command's own
 * confirmation applies; in production it also needs --allow-production and the
 * operator must type the database name interactively, even when --force is given.
 */
trait GuardsDestructiveRuns
{
    protected function destructiveRunAllowed(): bool
    {
        if (!app()->environment('production')) {
            return true;
        }

        if (!$this->option('allow-production')) {
            $this->error('رُفض التنفيذ: هذا الأمر يمسح بيانات ولا يعمل في بيئة الإنتاج إلا مع الخيار --allow-production.');
            return false;
        }

        if (!$this->input->isInteractive()) {
            $this->error('رُفض التنفيذ: الإنتاج يتطلب تأكيداً تفاعلياً بكتابة اسم قاعدة البيانات.');
            return false;
        }

        $expected = basename((string) DB::connection()->getDatabaseName());
        $this->warn("أنت على بيئة الإنتاج. قاعدة البيانات: {$expected}");
        $this->warn('تأكد من وجود نسخة احتياطية حديثة قبل المتابعة (php artisan iiis:backup-run).');

        if ($this->ask('اكتب اسم قاعدة البيانات للتأكيد') !== $expected) {
            $this->error('الاسم غير مطابق. أُلغيت العملية.');
            return false;
        }

        return true;
    }
}
