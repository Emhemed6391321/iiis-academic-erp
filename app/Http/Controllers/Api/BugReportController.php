<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BugReportController extends Controller
{
    /**
     * List all bug reports (admin view with filters).
     */
    public function index(Request $request): JsonResponse
    {
        // بلاغات الجميع (وبريد مُبلِّغيها) مقصورة على من يراجع النظام؛ غيره يرى بلاغاته فقط
        $user = $request->user() ?: Auth::user();
        $canReviewAll = $user && $user->hasPermission('system.monitor');

        $query = BugReport::with(['reporter:id,name,email', 'resolver:id,name'])
            ->orderByDesc('created_at');

        if (!$canReviewAll) {
            $query->where('reporter_id', $user?->id ?? 0);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('section_key')) {
            $query->where('section_key', $request->section_key);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('section_name', 'like', "%{$s}%");
            });
        }

        $reports = $query->paginate(min(100, max(1, (int) ($request->per_page ?? 25))));

        $scoped = fn () => BugReport::query()
            ->when(!$canReviewAll, fn ($q) => $q->where('reporter_id', $user?->id ?? 0));

        $stats = [
            'pending'     => $scoped()->where('status', 'pending')->count(),
            'in_progress' => $scoped()->where('status', 'in_progress')->count(),
            'resolved'    => $scoped()->where('status', 'resolved')->count(),
            'dismissed'   => $scoped()->where('status', 'dismissed')->count(),
            'total'       => $scoped()->count(),
            'avg_rating'  => round((float) $scoped()->avg('rating'), 1),
        ];

        return response()->json([
            'success' => true,
            'data'    => $reports->items(),
            'meta'    => [
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'total'        => $reports->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Submit a bug report (authenticated user).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_key'  => 'required|string|max:100',
            'section_name' => 'required|string|max:200',
            'category'     => 'required|in:ui,data,performance,access,calculation,other',
            'rating'       => 'required|integer|min:1|max:5',
            'title'        => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'diagnostics'  => 'nullable|array',
        ], [
            'title.required'       => 'يرجى كتابة عنوان موجز للمشكلة.',
            'description.required' => 'يرجى وصف المشكلة بالتفصيل.',
            'description.min'      => 'يجب أن يكون وصف المشكلة 10 أحرف على الأقل.',
            'rating.required'      => 'يرجى تقييم الصفحة.',
        ]);

        // التشخيص الملتقط تلقائياً من المتصفح؛ نحدّ حجمه حتى لا ينتفخ الجدول
        $diagnostics = $this->limitDiagnostics($validated['diagnostics'] ?? null);
        $hasDiagnostics = !empty($diagnostics['jsErrors']) || !empty($diagnostics['failedRequests']) || !empty($diagnostics['consoleLogs']);

        // الخطأ الملتقط يكفي كبلاغ إن لم يكتب المستخدم عنواناً أو وصفاً
        $validated['title'] = trim((string) ($validated['title'] ?? '')) ?: $this->autoTitle($diagnostics, $validated['section_name']);
        $validated['description'] = trim((string) ($validated['description'] ?? '')) ?: ($hasDiagnostics ? 'بلاغ تلقائي: تم التقاط سجل المتصفح والأخطاء المرتبطة بالصفحة.' : '');
        if (mb_strlen($validated['description']) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى وصف المشكلة (10 أحرف على الأقل).',
                'errors' => ['description' => ['يرجى وصف المشكلة (10 أحرف على الأقل).']],
            ], 422);
        }
        unset($validated['diagnostics']);

        // Compute priority: lower rating = higher priority
        $priority = match(true) {
            $validated['rating'] <= 1 && in_array($validated['category'], ['data', 'calculation', 'access']) => 5,
            $validated['rating'] <= 2 => 4,
            $validated['rating'] <= 3 => 3,
            $validated['category'] === 'access' => 3,
            default => 1,
        };

        $report = BugReport::create([
            ...$validated,
            'reporter_id'  => Auth::id(),
            'browser_info' => substr($request->header('User-Agent', ''), 0, 255),
            'url'          => $request->header('Referer', ''),
            'ip_address'   => $request->ip(),
            'priority'     => $priority,
            'category_label' => $this->getCategoryLabel($validated['category']),
            'diagnostics'  => $diagnostics,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'شكراً! تم استلام بلاغك وسيتم مراجعته من قِبل الفريق التقني قريباً.',
            'id'      => $report->id,
            'data'    => $report,
        ], 201);
    }

    /**
     * Update bug report status (admin only).
     */
    public function update(Request $request, BugReport $bugReport): JsonResponse
    {
        $validated = $request->validate([
            'status'      => 'required|in:pending,in_progress,resolved,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $bugReport->status = $validated['status'];
        $bugReport->admin_notes = $validated['admin_notes'] ?? $bugReport->admin_notes;

        if ($validated['status'] === 'resolved' && !$bugReport->resolved_at) {
            $bugReport->resolved_by = Auth::id();
            $bugReport->resolved_at = now();
        }

        $bugReport->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة البلاغ.',
            'data' => [
                'id'           => $bugReport->id,
                'status'       => $bugReport->status,
                'status_label' => $bugReport->status_label,
                'admin_notes'  => $bugReport->admin_notes,
                'resolved_at'  => $bugReport->resolved_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Delete a bug report (admin only).
     */
    public function destroy(BugReport $bugReport): JsonResponse
    {
        $bugReport->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف البلاغ.']);
    }

    /**
     * يقصّ سجلات التشخيص إلى حدود معقولة (عدد العناصر وطول كل نص) ويُبقي الحقول المعروفة فقط.
     */
    private function limitDiagnostics(?array $raw): array
    {
        if (!$raw) {
            return [];
        }

        $cut = fn ($value, int $max) => mb_substr((string) $value, 0, $max);
        $list = fn ($items, int $count) => array_slice(array_values(array_filter((array) $items, 'is_array')), -$count);

        $out = [
            'capturedAt' => $cut($raw['capturedAt'] ?? '', 40),
            'page' => array_map(fn ($v) => $cut($v, 500), array_intersect_key((array) ($raw['page'] ?? []), array_flip(['url', 'title', 'section', 'activeTab', 'studentId']))),
            'client' => array_map(fn ($v) => is_array($v) ? $v : $cut($v, 300), array_intersect_key((array) ($raw['client'] ?? []), array_flip(['userAgent', 'language', 'viewport', 'darkMode', 'online', 'timezone']))),
            'user' => array_map(fn ($v) => $cut($v, 200), array_intersect_key((array) ($raw['user'] ?? []), array_flip(['name', 'role', 'isSuperAdmin']))),
            'consoleLogs' => array_map(fn ($e) => [
                'level' => $cut($e['level'] ?? '', 10),
                'at' => $cut($e['at'] ?? '', 40),
                'message' => $cut($e['message'] ?? '', 1500),
            ], $list($raw['consoleLogs'] ?? [], 80)),
            'jsErrors' => array_map(fn ($e) => [
                'type' => $cut($e['type'] ?? '', 20),
                'at' => $cut($e['at'] ?? '', 40),
                'message' => $cut($e['message'] ?? '', 800),
                'source' => $cut($e['source'] ?? '', 300),
                'stack' => $cut($e['stack'] ?? '', 2000),
            ], $list($raw['jsErrors'] ?? [], 40)),
            'failedRequests' => array_map(fn ($e) => [
                'at' => $cut($e['at'] ?? '', 40),
                'method' => $cut($e['method'] ?? '', 10),
                'url' => $cut($e['url'] ?? '', 400),
                'status' => (int) ($e['status'] ?? 0),
                'ms' => (int) ($e['ms'] ?? 0),
                'response' => $cut($e['response'] ?? '', 800),
            ], $list($raw['failedRequests'] ?? [], 40)),
        ];

        // حدّ نهائي للحجم الكلي (300 كيلوبايت)
        if (strlen(json_encode($out, JSON_UNESCAPED_UNICODE)) > 300000) {
            $out['consoleLogs'] = array_slice($out['consoleLogs'], -20);
            $out['jsErrors'] = array_slice($out['jsErrors'], -10);
            $out['failedRequests'] = array_slice($out['failedRequests'], -10);
            $out['truncated'] = true;
        }

        return $out;
    }

    /**
     * عنوان تلقائي للبلاغ من أول خطأ ملتقط أو أول طلب فاشل، عند غياب عنوان من المستخدم.
     */
    private function autoTitle(array $diagnostics, string $sectionName): string
    {
        if (!empty($diagnostics['jsErrors'][0]['message'])) {
            return mb_substr('خطأ في ' . $sectionName . ': ' . $diagnostics['jsErrors'][0]['message'], 0, 250);
        }
        if (!empty($diagnostics['failedRequests'][0])) {
            $r = $diagnostics['failedRequests'][0];
            return mb_substr('طلب فاشل (' . $r['status'] . ') ' . $r['method'] . ' ' . $r['url'], 0, 250);
        }
        return 'بلاغ عن ' . $sectionName;
    }

    private function getCategoryLabel(string $category): string
    {
        return match($category) {
            'ui'          => 'واجهة المستخدم',
            'data'        => 'بيانات غير صحيحة',
            'performance' => 'بطء في الأداء',
            'access'      => 'مشكلة صلاحيات',
            'calculation' => 'خطأ في الحسابات',
            default       => 'أخرى',
        };
    }
}
