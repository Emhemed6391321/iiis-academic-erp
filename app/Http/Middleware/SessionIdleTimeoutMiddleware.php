<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionIdleTimeoutMiddleware
{
    /**
     * Inactivity timeout in seconds (30 minutes).
     */
    protected int $timeout = 1800;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = $request->session()->get('last_user_activity');
            $now = time();

            if ($lastActivity && ($now - $lastActivity) > $this->timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'code' => 'SESSION_EXPIRED',
                        'message' => 'انتهت جلسة العمل بسبب انقضاء مهلة الخمول (30 دقيقة). يرجى إعادة تسجيل الدخول.',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'انتهت جلسة العمل تلقائياً بسبب عدم النشاط لمدة 30 دقيقة حفاظاً على سرية البيانات.',
                ]);
            }

            $request->session()->put('last_user_activity', $now);
        }

        return $next($request);
    }
}
