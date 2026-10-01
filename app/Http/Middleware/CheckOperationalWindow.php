<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\OperationalWindow;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Auth;

class CheckOperationalWindow
{
    /**
     * Handle an incoming request and ensure the operational window is open.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $windowType (e.g. REGISTRATION, S1_COURSEWORK, S1_FINAL, S2_FINAL, SECOND_ROUND)
     */
    public function handle(Request $request, Closure $next, string $windowType): Response
    {
        $user = Auth::user();

        // Super Admin bypasses time windows for emergency operational fixes
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        if (!$currentAcademicYear) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد عام دراسي نشط حالياً على النظام.',
            ], 403);
        }

        $window = OperationalWindow::where('academic_year_id', $currentAcademicYear->id)
            ->where('window_type', $windowType)
            ->where('is_active', true)
            ->first();

        if (!$window) {
            return response()->json([
                'success' => false,
                'message' => 'النافذة التشغيلية المحددة لهذه العملية غير مفعلة بالنظام.',
            ], 403);
        }

        $branchId = $user ? $user->branch_id : null;

        if (!$window->isOpenForBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => 'عفواً، انتهت الفترة المحددة نظاماً لهذه العملية وتم إغلاق النظام آلياً للفرع.',
                'window_title' => $window->title,
                'closed_at' => $window->end_at->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}
