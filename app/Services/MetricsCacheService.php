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
    const CACHE_TTL_SECONDS = 300; // 5 minutes TTL for ultra-fast response with immediate invalidation on mutations
    const HQ_SUMMARY_KEY_PREFIX = 'iiis_metrics_hq_summary_';
    const BRANCH_METRICS_KEY_PREFIX = 'iiis_metrics_branch_';

    /**
     * Get or calculate cached HQ / Branch executive dashboard summary.
     */
    public function getHQSummary(?int $branchId = null, bool $forceRefresh = false): array
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $cacheSuffix = ($currentYear?->id ?? 'default') . ($branchId ? "_branch_{$branchId}" : '_hq');
        $cacheKey = self::HQ_SUMMARY_KEY_PREFIX . $cacheSuffix;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($currentYear, $branchId) {
            $studentQuery = Student::query();
            $batchQuery = GradeBatch::query();
            $propertyQuery = \App\Models\Property::query();
            $contractQuery = \App\Models\BranchContract::query();
            $installmentQuery = \App\Models\ContractInstallment::query();
            $attendanceQuery = \App\Models\StudentAttendance::whereDate('record_date', Carbon::today());
            $branchRequestQuery = \App\Models\BranchRequest::query();
            $verificationQuery = \App\Models\DocumentVerification::query();

            if ($branchId) {
                $studentQuery->where('branch_id', $branchId);
                $batchQuery->where('branch_id', $branchId);
                $propertyQuery->where('branch_id', $branchId);
                $contractQuery->where('branch_id', $branchId);
                $installmentQuery->whereHas('contract', fn($q) => $q->where('branch_id', $branchId));
                $attendanceQuery->where('branch_id', $branchId);
                $branchRequestQuery->where('branch_id', $branchId);
                $verificationQuery->whereHas('student', fn($q) => $q->where('branch_id', $branchId));
            }

            // تجميع إحصائيات الطلاب في استعلام واحد عالي الكفاءة
            $studentCounts = (clone $studentQuery)->toBase()->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN academic_status = 'ENROLLED_ACTIVE' THEN 1 ELSE 0 END) as enrolled,
                SUM(CASE WHEN academic_status = 'PENDING_HQ' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN academic_status = 'NEW_DRAFT' THEN 1 ELSE 0 END) as draft
            ")->first();

            $totalStudents        = (int) ($studentCounts->total ?? 0);
            $enrolledStudents     = (int) ($studentCounts->enrolled ?? 0);
            $pendingStudents      = (int) ($studentCounts->pending ?? 0);
            $draftStudents        = (int) ($studentCounts->draft ?? 0);

            // تجميع دفاتر الدرجات في استعلام واحد
            $batchCounts = (clone $batchQuery)->toBase()->selectRaw("
                SUM(CASE WHEN status = 'SUBMITTED_TO_HQ' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'HQ_APPROVED' THEN 1 ELSE 0 END) as approved
            ")->first();

            $pendingGradeBatches  = (int) ($batchCounts->pending ?? 0);
            $approvedGradeBatches = (int) ($batchCounts->approved ?? 0);

            // Properties & Contracts KPIs
            $totalProperties    = (clone $propertyQuery)->count();

            $today = Carbon::today()->toDateString();
            $today30 = Carbon::today()->addDays(30)->toDateString();

            $contractCounts = (clone $contractQuery)->toBase()->selectRaw("
                SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'ACTIVE' AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring
            ", [$today, $today30])->first();

            $activeContracts    = (int) ($contractCounts->active ?? 0);
            $expiringContracts  = (int) ($contractCounts->expiring ?? 0);

            $installmentStats = (clone $installmentQuery)->toBase()->selectRaw("
                SUM(CASE WHEN payment_status = 'PAID' THEN amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN payment_status != 'PAID' OR payment_status IS NULL THEN amount ELSE 0 END) as total_due
            ")->first();

            $totalPaidInstallments = (float) ($installmentStats->total_paid ?? 0);
            $totalDueInstallments  = (float) ($installmentStats->total_due ?? 0);

            // Attendance & Document Verification KPIs
            $todayAttendance     = (clone $attendanceQuery)->count();
            $deprivationAlerts   = (clone $branchRequestQuery)->where('request_type', 'ACADEMIC')
                ->where('title', 'like', '%تجاوز نسبة الغياب%')
                ->count();
            $verifiedDocuments   = (clone $verificationQuery)->count();
            $pendingRequests     = (clone $branchRequestQuery)->where('status', 'PENDING')->count();

            // Branches statistics (Active operational branches only)
            $branchesQuery = Branch::where('is_active', true)->where('branch_status', 'ACTIVE');
            if ($branchId) {
                $branchesQuery->where('id', $branchId);
            }
            $branches = $branchesQuery
                ->withCount([
                    'students as total_students_count',
                    'students as pending_students_count' => fn($q) => $q->where('academic_status', 'PENDING_HQ'),
                    'students as active_students_count'  => fn($q) => $q->where('academic_status', 'ENROLLED_ACTIVE'),
                    'gradeBatches as pending_batches_count' => fn($q) => $q->where('status', 'SUBMITTED_TO_HQ'),
                ])
                ->get();

            // لوحة الفرع لا تعرض عدد الفروع الموقوفة في باقي المنظومة
            $suspendedBranchesQuery = Branch::where('branch_status', 'SUSPENDED');
            if ($branchId) {
                $suspendedBranchesQuery->where('id', $branchId);
            }
            $suspendedBranchesCount = $suspendedBranchesQuery->count();

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
                    'suspended_branches'     => $suspendedBranchesCount,
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
        $yearId = $currentYear?->id ?? 'default';
        Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . $yearId . '_hq');
        Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . $yearId);
        Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . 'default_hq');
        Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . 'default');

        $branches = Branch::pluck('id');
        foreach ($branches as $bId) {
            Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . $yearId . "_branch_{$bId}");
            Cache::forget(self::HQ_SUMMARY_KEY_PREFIX . "default_branch_{$bId}");
        }
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
