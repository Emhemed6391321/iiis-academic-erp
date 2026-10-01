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
        //
    }

    public function boot(): void
    {
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
