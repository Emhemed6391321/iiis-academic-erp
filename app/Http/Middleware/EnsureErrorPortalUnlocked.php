<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحمي بوابة الأخطاء بكلمة سر مستقلة عن حسابات المستخدمين.
 * أي طلب غير مفتوح يُحوَّل إلى صفحة كلمة السر، وتنتهي الجلسة بعد مدة خمول.
 */
class EnsureErrorPortalUnlocked
{
    public const SESSION_KEY = 'error_portal_unlocked_at';

    public function handle(Request $request, Closure $next): Response
    {
        $unlockedAt = $request->session()->get(self::SESSION_KEY);
        $idle = config('error_portal.idle_minutes', 30) * 60;

        if (! $unlockedAt || (time() - (int) $unlockedAt) > $idle) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('error-portal.login');
        }

        $request->session()->put(self::SESSION_KEY, time());

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
