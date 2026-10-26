<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\OperationalWindow;
use App\Models\AcademicYear;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CheckOperationalWindow
{
    /**
     * Handle an incoming request and ensure the operational window is open.
     *
     * A missing or disabled window is never replaced by an open one. Windows are
     * provisioned explicitly (see `windows:initialize-missing`) and opened by HQ.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $windowType (e.g. REGISTRATION, S1_COURSEWORK, S1_FINAL, S2_FINAL, SECOND_ROUND)
     */
    public function handle(Request $request, Closure $next, string $windowType): Response
    {
        $user = Auth::user();

        // Super Admin may bypass windows for emergency fixes; state-changing bypasses are audited.
        if ($user && $user->isSuperAdmin()) {
            if (!$request->isMethodSafe()) {
                SystemAuditTrail::log(
                    'OPERATIONAL_WINDOW_OVERRIDE',
                    "تجاوز طارئ للنافذة التشغيلية ({$windowType}) بواسطة المدير العام",
                    ['window_type' => $windowType, 'path' => $request->path()],
                    $user->id,
                    $user->branch_id,
                    null,
                    null,
                    [],
                    [],
                    'WARNING',
                    $request
                );
            }

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
            ->first();

        if (!$window) {
            return $this->reject(
                $request,
                $windowType,
                'NOT_CONFIGURED',
                'الفترة التشغيلية لهذه العملية غير مهيأة. يجب أن تضبطها الإدارة العامة قبل إجرائها.'
            );
        }

        if (!$window->is_active) {
            return $this->reject($request, $windowType, 'DISABLED', 'هذه الفترة التشغيلية موقوفة من الإدارة العامة.', $window);
        }

        $branchId = $user ? $user->branch_id : null;

        if (!$window->isOpenForBranch($branchId)) {
            if (Carbon::now()->lt($window->start_at)) {
                return $this->reject($request, $windowType, 'NOT_STARTED', 'لم تبدأ الفترة التشغيلية لهذه العملية بعد.', $window);
            }

            return $this->reject(
                $request,
                $windowType,
                'CLOSED',
                'عفواً، انتهت الفترة المحددة نظاماً لهذه العملية. بانقضاء الموعد المحدد، لا يُسمح للمعهد بإدخال الدرجات أو التسجيل، ويتحمل مدير المعهد المسؤولية عن ذلك، وفق اللوائح والتعليمات المعتمدة.',
                $window
            );
        }

        return $next($request);
    }

    private function reject(Request $request, string $windowType, string $status, string $message, ?OperationalWindow $window = null): Response
    {
        // A refused attempt to act outside the window is a forensic event, the same as a refused permission.
        $user = Auth::user();
        SystemAuditTrail::log(
            'UNAUTHORIZED_ACCESS_ATTEMPT',
            "محاولة تنفيذ عملية خارج النافذة التشغيلية ({$windowType}) - الحالة [{$status}] - المسار [{$request->path()}]",
            [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'window_type' => $windowType,
                'window_status' => $status,
                'user_role' => $user?->role?->name,
                'branch_id' => $user?->branch_id,
            ],
            $user?->id,
            $user?->branch_id,
            null,
            null,
            [],
            [],
            'WARNING',
            $request
        );

        $payload = [
            'success' => false,
            'window_status' => $status,
            'message' => $message,
        ];

        if ($window) {
            $payload['window_title'] = $window->title;
            $payload['closed_at'] = $window->end_at->toIso8601String();
        }

        return response()->json($payload, 403);
    }
}
