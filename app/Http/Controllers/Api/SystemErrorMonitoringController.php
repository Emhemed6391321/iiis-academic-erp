<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemErrorMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Exception;

class SystemErrorMonitoringController extends Controller implements HasMiddleware
{
    protected SystemErrorMonitoringService $service;

    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if (!$user || !$user->isSuperAdmin()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'وصول محظور: مركز مراقبة أخطاء النظام مخصص حصراً للمستخدم الرئيسي (Super Admin).',
                    ], 403);
                }
                return $next($request);
            }, except: ['reportJs']),
        ];
    }

    public function __construct(SystemErrorMonitoringService $service)
    {
        $this->service = $service;
    }

    /**
     * Get list of error logs with KPIs.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'error_type',
            'severity',
            'status',
            'from_date',
            'to_date',
            'per_page'
        ]);

        $logs = $this->service->getFilteredLogs($filters);
        $kpis = $this->service->getDashboardKPIs();

        return response()->json([
            'status' => 'success',
            'data' => [
                'logs' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                ],
                'kpis' => $kpis,
            ],
        ]);
    }

    /**
     * Show details of a single error log.
     */
    public function show(int $id): JsonResponse
    {
        $log = \App\Models\SystemErrorLog::find($id);
        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => 'سجل الخطأ غير موجود',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $log,
        ]);
    }

    /**
     * Update error status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:NEW,IN_REVIEW,UNDER_REVIEW,CAUSE_IDENTIFIED,RESOLVED,VERIFIED,CLOSED,IGNORED',
            'notes' => 'nullable|string|max:1000',
        ]);

        $log = $this->service->updateStatus($id, $request->input('status'), $request->input('notes'));
        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => 'تعذر تحديث حالة الخطأ',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الخطأ بنجاح',
            'data' => $log,
        ]);
    }

    /**
     * Report a JavaScript error from client-side.
     */
    public function reportJs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'file' => 'nullable|string|max:500',
            'line' => 'nullable|integer',
            'stack' => 'nullable|string',
            'url' => 'nullable|string|max:1000',
            'severity' => 'nullable|string|in:CRITICAL,HIGH,MEDIUM,LOW',
            'context' => 'nullable|array',
        ]);

        $log = $this->service->recordJsError($data);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل خطأ JavaScript بنجاح',
            'error_id' => $log ? $log->error_id : null,
        ]);
    }

    /**
     * Trigger a deliberate test error (Requirement #16).
     */
    public function triggerTestError(Request $request): JsonResponse
    {
        $type = $request->input('type', 'PHP');

        if ($type === 'DATABASE') {
            try {
                \Illuminate\Support\Facades\DB::select('SELECT * FROM non_existent_table_for_error_test_123');
            } catch (\Throwable $e) {
                $log = $this->service->recordThrowable($e, 'LOW', 'DATABASE_ERROR');
                if ($log) {
                    $log->update([
                        'status' => 'RESOLVED',
                        'severity' => 'LOW',
                        'resolution_notes' => 'سجل اختبار تجريبي معتمد لنظام الرصد (Database Test Run) - تم إغلاقه آلياً.',
                        'resolved_at' => now(),
                        'resolved_by_user_name' => 'نظام الرصد الآلي (Auto-Test)',
                    ]);
                }
                return response()->json([
                    'status' => 'success',
                    'message' => 'تم توليد واعتراض خطأ قاعدة بيانات تجريبي بنجاح وتسجيله كاختبار معتمد ومغلق.',
                    'error_id' => $log ? $log->error_id : null,
                ]);
            }
        }

        try {
            throw new \Exception("خطأ برمجي تجريبي لاختبار نظام المراقبة المركزي (Test Exception on " . date('Y-m-d H:i:s') . ")");
        } catch (\Throwable $e) {
            $log = $this->service->recordThrowable($e, 'LOW', 'ERROR');
            if ($log) {
                $log->update([
                    'status' => 'RESOLVED',
                    'severity' => 'LOW',
                    'resolution_notes' => 'سجل اختبار تجريبي معتمد لنظام الرصد (PHP Test Run) - تم إغلاقه آلياً.',
                    'resolved_at' => now(),
                    'resolved_by_user_name' => 'نظام الرصد الآلي (Auto-Test)',
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'تم توليد واعتراض خطأ تجريبي بنجاح وتسجيله كاختبار معتمد ومغلق!',
                'error_id' => $log ? $log->error_id : null,
            ]);
        }
    }

    /**
     * Purge all synthetic test errors from system error logs.
     */
    public function clearTestLogs(): JsonResponse
    {
        $deleted = \App\Models\SystemErrorLog::where('url', 'like', '%trigger-test%')
            ->orWhere('message', 'like', '%non_existent_table_for_error_test_123%')
            ->orWhere('message', 'like', '%خطأ برمجي تجريبي%')
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => "تم تنظيف وحذف {$deleted} من سجلات الأخطاء الاختبارية والتجريبية بنجاح.",
            'deleted_count' => $deleted,
        ]);
    }
}
