<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ErrorPortalHash extends Command
{
    protected $signature = 'error-portal:hash';

    protected $description = 'يولّد هاش كلمة سر بوابة الأخطاء لوضعه في ERROR_PORTAL_PASSWORD_HASH';

    public function handle(): int
    {
        $password = (string) $this->secret('أدخل كلمة السر (12 حرفاً على الأقل)');

        if (mb_strlen($password) < 12) {
            $this->error('كلمة السر أقصر من 12 حرفاً.');
            return self::FAILURE;
        }

        // علامات الاقتباس المفردة تمنع .env من تفسير $ كمتغير
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->line("ERROR_PORTAL_PASSWORD_HASH='" . $hash . "'");
        $this->info('ضع السطر أعلاه في ملف .env ثم نفّذ php artisan config:clear');

        return self::SUCCESS;
    }
}
