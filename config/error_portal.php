<?php

return [
    /*
    | بوابة الأخطاء المخفية: صفحة مستقلة عن حسابات النظام لا تُفتح إلا بكلمة سر.
    | تبقى معطّلة (404 لكل مساراتها) ما لم يُضبط ERROR_PORTAL_PASSWORD_HASH في .env.
    |
    | توليد قيمة الهاش:  php artisan error-portal:hash
    */
    'path' => trim((string) env('ERROR_PORTAL_PATH', 'ops-x7k2q9'), '/'),

    'password_hash' => (string) env('ERROR_PORTAL_PASSWORD_HASH', ''),

    // مدة الخمول بالدقائق قبل طلب كلمة السر من جديد
    'idle_minutes' => (int) env('ERROR_PORTAL_IDLE_MINUTES', 30),

    // محاولات الدخول الخاطئة المسموحة لكل IP خلال نافذة الحظر
    'max_attempts' => 5,
    'decay_seconds' => 900,
];
