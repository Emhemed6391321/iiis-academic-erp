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
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\SessionIdleTimeoutMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'check.window'          => CheckOperationalWindow::class,
            'academic.year.active'  => EnsureActiveAcademicYear::class,
            'enforce.mfa'           => \App\Http\Middleware\EnforceMfaMiddleware::class,
            'force.password_change' => \App\Http\Middleware\ForcePasswordChangeMiddleware::class,
            'security.headers'      => \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Trust proxies to prevent IP spoofing behind reverse proxies (Requirement 1.7)
        $middleware->trustProxies(at: '*');
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            try {
                app(\App\Services\SystemErrorMonitoringService::class)->recordThrowable($e);
            } catch (\Throwable $ignore) {
                // Safeguard to never block default reporting
            }
        });
    })->create();

