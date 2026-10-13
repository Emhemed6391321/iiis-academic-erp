<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckOperationalWindow;
use App\Http\Middleware\EnsureActiveAcademicYear;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CompressResponse::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\SessionIdleTimeoutMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\RejectCrossSiteApiWrites::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CompressResponse::class,
            \App\Http\Middleware\SessionIdleTimeoutMiddleware::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'check.window'          => CheckOperationalWindow::class,
            'academic.year.active'  => EnsureActiveAcademicYear::class,
            'enforce.mfa'           => \App\Http\Middleware\EnforceMfaMiddleware::class,
            'force.password_change' => \App\Http\Middleware\ForcePasswordChangeMiddleware::class,
            'security.headers'      => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'permission'            => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Exempt logout and api from CSRF token verification to prevent 419 Page Expired errors
        $middleware->validateCsrfTokens(except: [
            'logout',
            '/logout',
            'api/*',
        ]);

        // Trust proxies to prevent IP spoofing behind reverse proxies (Requirement 1.7)
        $proxies = env('TRUSTED_PROXIES');
        if (!empty($proxies)) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            try {
                app(\App\Services\SystemErrorMonitoringService::class)->recordThrowable($e);
            } catch (\Throwable $ignore) {
                // Safeguard to never block default reporting
            }
        });

        // Gracefully handle session expiration / CSRF mismatch (prevent default 419 error screen)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Auth::logout();
            try {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $ignored) {}

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'انتهت صلاحية جلسة العمل، يرجى إعادة تسجيل الدخول.'
                ], 419);
            }

            $message = ($request->is('logout') || $request->is('*/logout'))
                ? 'تم تسجيل الخروج بنجاح.'
                : 'انتهت صلاحية جلسة العمل بسبب عدم النشاط. يرجى تسجيل الدخول مجدداً.';

            return redirect('/login')->with('status', $message);
        });
    })->create();

