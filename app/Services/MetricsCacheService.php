<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\GradeBatch;
use App\Models\OperationalWindow;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MetricsCacheService
{
    const CACHE_TTL_SECONDS = 3600; // 1 hour default TTL
    const HQ_SUMMARY_KEY_PREFIX = 'manhal_metrics_hq_summary_';
    const BRANCH_METRICS_KEY_PREFIX = 'manhal_metrics_branch_';

    /**
     * Get or calculate cached HQ executive dashboard summary.
     */
    public function getHQSummary(bool $forceRefresh = false): array
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $cacheKey = self::HQ_SUMMARY_KEY_PREFIX . ($currentYear?->id ?? 'default');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($currentYear) {
            $totalStudents       = Student::count();
            $enrolledStudents    = Student::where('academic_status', 'ENROLLED_ACTIVE')->count();
            $pendingStudents     = Student::where('academic_status', 'PENDING_HQ')->count();
            $draftStudents       = Student::where('academic_status', 'NEW_DRAFT')->count();

            $pendingGradeBatches  = GradeBatch::where('status', 'SUBMITTED_TO_HQ')->count();
            $approvedGradeBatches = GradeBatch::where('status', 'HQ_APPROVED')->count();

            // Properties & Contracts KPIs
            $totalProperties    = \App\Models\Property::count();
            $activeContracts    = \App\Models\BranchContract::where('status', 'ACTIVE')->count();
            $expiringContracts  = \App\Models\BranchContract::where('status', 'ACTIVE')
                ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(30)])
                ->count();
            $totalPaidInstallments = (float) \App\Models\ContractInstallment::where('status', 'PAID')->sum('amount');
            $totalDueInstallments  = (float) \App\Models\ContractInstallment::where('status', '!=', 'PAID')->sum('amount');

            // Attendance & Document Verification KPIs
            $todayAttendance     = \App\Models\StudentAttendance::whereDate('record_date', Carbon::today())->count();
            $deprivationAlerts   = \App\Models\BranchRequest::where('request_type', 'ACADEMIC')
                ->where('title', 'like', '%تجاوز نسبة الغياب%')
                ->count();
            $verifiedDocuments   = \App\Models\DocumentVerification::count();
            $pendingRequests     = \App\Models\BranchRequest::where('status', 'PENDING')->count();

            // Branches statistics
            $branches = Branch::where('is_active', true)
                ->withCount([
                    'students as total_students_count',
                    'students as pending_students_count' => fn($q) => $q->where('academic_status', 'PENDING_HQ'),
                    'students as active_students_count'  => fn($q) => $q->where('academic_status', 'ENROLLED_ACTIVE'),
                    'gradeBatches as pending_batches_count' => fn($q) => $q->where('status', 'SUBMITTED_TO_HQ'),
                ])
                ->get();

            // Active operational windows
            $windows = OperationalWindow::where('academic_year_id', $currentYear?->id)
                ->where('is_active', true)
                ->get()
                ->map(function ($w) {
                    $now = Carbon::now();
                    $isOpen = $now->between($w->start_at, $w->end_at);
                    $diff = $now->diff($w->end_at);
                    return [
                        'id'                => $w->id,
                        'title'             => $w->title,
                        'window_type'       => $w->window_type,
                        'is_open'           => $isOpen,
                        'start_at'          => $w->start_at->toIso8601String(),
                        'end_at'            => $w->end_at->toIso8601String(),
                        'remaining_days'    => $isOpen ? $diff->days : 0,
                        'remaining_hours'   => $isOpen ? $diff->h : 0,
                        'remaining_minutes' => $isOpen ? $diff->i : 0,
                    ];
                });

            return [
                'cached_at' => Carbon::now()->toIso8601String(),
                'kpis' => [
                    'total_students'         => $totalStudents,
                    'enrolled_students'      => $enrolledStudents,
                    'pending_students'       => $pendingStudents,
                    'draft_students'         => $draftStudents,
                    'pending_grade_batches'  => $pendingGradeBatches,
                    'approved_grade_batches' => $approvedGradeBatches,
                    'total_properties'       => $totalProperties,
                    'active_contracts'       => $activeContracts,
                    'expiring_contracts'     => $expiringContracts,
                    'total_paid'             => $totalPaidInstallments,
                    'total_due'              => $totalDueInstallments,
                    'today_attendance'       => $todayAttendance,
                    'deprivation_alerts'     => $deprivationAlerts,
                    'verified_documents'     => $verifiedDocuments,
                    'pending_requests'       => $pendingRequests,
                ],
                'branches'              => $branches,
                'operational_windows'   => $windows,
                'current_academic_year' => $currentYear,
            ];
        });
    }

    /**
     * Invalidate cached metrics on state-changing events.
     */
    public function invalidateHQSummary(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . ($currentYear?->id ?? 'default'));
        Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . 'default');
    }

    public function invalidateBranchMetrics(int $branchId): void
    {
        Cache::forget(self::BRANCH_METRICS_KEY_PREFIX . $branchId);
        $this->invalidateHQSummary();
    }

    public function invalidateAll(): void
    {
        $this->invalidateHQSummary();
        $branches = Branch::pluck('id');
        foreach ($branches as $branchId) {
            Cache::forget(self::BRANCH_METRICS_KEY_PREFIX . $branchId);
        }
    }
}
