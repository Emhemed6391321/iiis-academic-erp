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
            'title'        => 'required|string|max:255',
            'description'  => 'required|string|min:10|max:2000',
        ], [
            'title.required'       => 'يرجى كتابة عنوان موجز للمشكلة.',
            'description.required' => 'يرجى وصف المشكلة بالتفصيل.',
            'description.min'      => 'يجب أن يكون وصف المشكلة 10 أحرف على الأقل.',
            'rating.required'      => 'يرجى تقييم الصفحة.',
        ]);

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
