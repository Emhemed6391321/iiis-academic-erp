<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Standalone XLSX Engine fallback autoloader
        spl_autoload_register(function ($class) {
            if ($class === 'Shuchkin\\SimpleXLSX' && !class_exists('Shuchkin\\SimpleXLSX', false)) {
                $file = app_path('Support/SimpleXLSX.php');
                if (file_exists($file)) {
                    require_once $file;
                }
            } elseif ($class === 'Shuchkin\\SimpleXLSXGen' && !class_exists('Shuchkin\\SimpleXLSXGen', false)) {
                $file = app_path('Support/SimpleXLSXGen.php');
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });
    }

    public function boot(): void
    {
        // Account administration (delete, reset password) has its own bucket per user. The anonymous form throttle:10,1
        // shares one counter with every other throttled route of the same user, so ordinary dashboard traffic used it up.
        \Illuminate\Support\Facades\RateLimiter::for('account-admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('account-admin|' . ($request->user()?->id ?? $request->ip()));
        });

        // Production hard-gate: the shield bypass secret must never exist in production.
        if ($this->app->environment('production') && !empty(config('app.dev_bypass_secret'))) {
            throw new \RuntimeException('CRITICAL SECURITY VIOLATION: DEV_BYPASS is strictly prohibited in production environment.');
        }

        // RBAC Universal Gate Hook
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
            if ($user && method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability) ? true : null;
            }
            return null;
        });

        try {
            $activeYear = \App\Models\AcademicYear::where('is_current', true)->first()
                ?: \App\Models\AcademicYear::orderByDesc('id')->first();
            $allAcademicYears = \App\Models\AcademicYear::orderByDesc('is_current')->orderByDesc('id')->get();
            \Illuminate\Support\Facades\View::share('currentAcademicYear', $activeYear);
            \Illuminate\Support\Facades\View::share('allAcademicYears', $allAcademicYears);
            \Illuminate\Support\Facades\View::share('instituteProfile', \App\Services\AdminSettingsService::getInstituteProfile());
        } catch (\Throwable $e) {
            // During migration or boot before db ready
        }

        // Automated Event-Driven Cache Invalidation for Dashboard Metrics
        \App\Models\Student::saved(function ($student) {
            app(\App\Services\MetricsCacheService::class)->invalidateBranchMetrics($student->branch_id ?? 0);
        });
        \App\Models\Student::deleted(function ($student) {
            app(\App\Services\MetricsCacheService::class)->invalidateBranchMetrics($student->branch_id ?? 0);
        });
        \App\Models\GradeBatch::saved(function () {
            app(\App\Services\MetricsCacheService::class)->invalidateHQSummary();
        });
        \App\Models\BranchContract::saved(function () {
            app(\App\Services\MetricsCacheService::class)->invalidateHQSummary();
        });
    }
}
