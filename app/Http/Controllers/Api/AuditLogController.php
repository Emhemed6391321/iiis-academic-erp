<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\GradeLog;
use App\Models\SystemAuditTrail;

class AuditLogController extends Controller
{
    public function getGradeLogs(Request $request): JsonResponse
    {
        $logs = GradeLog::withoutGlobalScopes()
            ->with(['student', 'course', 'branch', 'user'])
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    public function getSystemAuditTrails(Request $request): JsonResponse
    {
        $trails = SystemAuditTrail::withoutGlobalScopes()
            ->with(['user', 'branch'])
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'trails' => $trails,
        ]);
    }

    public function getLiveNotificationsAndAlerts(Request $request): JsonResponse
    {
        $alerts = [];
        $unreadCount = 0;

        // 1. Pending Student Workflow Requests (طلبات الطلاب بانتظار موافقة رئيس قسم الدراسة والامتحانات)
        $pendingEnrollment = \App\Models\EnrollmentStatusRequest::with('student.branch')
            ->where('final_status', 'PENDING')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        foreach ($pendingEnrollment as $req) {
            $studentName = $req->student ? ($req->student->first_name . ' ' . $req->student->family_name) : 'طالب';
            $branchName = $req->student?->branch?->name ?? 'الفرع';
            $typeLabel = $req->request_type === 'PAUSE' ? 'إيقاف قيد' : 'تجديد قيد';

            $alerts[] = [
                'id' => 'wf_status_' . $req->id,
                'title' => "طلب {$typeLabel} بانتظار الاعتماد المركزي",
                'description' => "مقدم بتأييد مدير {$branchName} للطالب {$studentName}",
                'time_ago' => $req->created_at ? $req->created_at->diffForHumans() : 'حديثاً',
                'badge_color' => 'bg-amber-500',
                'target_section' => 'student_workflow',
                'type' => 'WORKFLOW',
            ];
            $unreadCount++;
        }

        // 2. Pending Study Type Change Requests
        $pendingSystem = \App\Models\StudyTypeChangeRequest::with('student.branch')
            ->where('final_status', 'PENDING')
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();

        foreach ($pendingSystem as $req) {
            $studentName = $req->student ? ($req->student->first_name . ' ' . $req->student->family_name) : 'طالب';
            $branchName = $req->student?->branch?->name ?? 'الفرع';

            $alerts[] = [
                'id' => 'wf_system_' . $req->id,
                'title' => "طلب تغيير صفة القيد (نظامي/انتساب)",
                'description' => "مقدم بتأييد مدير {$branchName} للطالب {$studentName}",
                'time_ago' => $req->created_at ? $req->created_at->diffForHumans() : 'حديثاً',
                'badge_color' => 'bg-indigo-500',
                'target_section' => 'student_workflow',
                'type' => 'WORKFLOW',
            ];
            $unreadCount++;
        }

        // 3. Pending Student Transfers
        $pendingTransfers = \App\Models\StudentTransfer::with(['student', 'fromBranch', 'toBranch'])
            ->where('status', 'PENDING')
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();

        foreach ($pendingTransfers as $req) {
            $studentName = $req->student ? ($req->student->first_name . ' ' . $req->student->family_name) : 'طالب';
            $fromName = $req->fromBranch?->name ?? 'الفرع المصدر';
            $toName = $req->toBranch?->name ?? 'الفرع المستقبل';

            $alerts[] = [
                'id' => 'wf_transfer_' . $req->id,
                'title' => "طلب نقل طالب بين الفروع ({$fromName} ➔ {$toName})",
                'description' => "مقدم بتأييد مدير الفرع للطالب {$studentName}",
                'time_ago' => $req->created_at ? $req->created_at->diffForHumans() : 'حديثاً',
                'badge_color' => 'bg-purple-500',
                'target_section' => 'student_workflow',
                'type' => 'TRANSFER',
            ];
            $unreadCount++;
        }

        // 4. Pending Grade Batches (دفعات الكنترول)
        $pendingBatches = \App\Models\GradeBatch::with(['branch', 'course'])
            ->whereIn('status', ['SUBMITTED_TO_HQ', 'PENDING'])
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();

        foreach ($pendingBatches as $b) {
            $branchName = $b->branch?->name ?? 'الفرع';
            $courseName = $b->course?->name ?? 'المقرر';

            $alerts[] = [
                'id' => 'batch_' . $b->id,
                'title' => "دفعة درجات ورصد بانتظار الاعتماد المركزي",
                'description' => "مرفوعة من {$branchName} لمقرر {$courseName}",
                'time_ago' => $b->submitted_at ? \Carbon\Carbon::parse($b->submitted_at)->diffForHumans() : 'حديثاً',
                'badge_color' => 'bg-blue-500',
                'target_section' => 'study_exams_hub',
                'type' => 'EXAM',
            ];
            $unreadCount++;
        }

        // 5. Recent Sensitive Audit Trails
        $recentAudits = SystemAuditTrail::with('user')
            ->orderBy('id', 'desc')
            ->limit(4)
            ->get();

        foreach ($recentAudits as $trail) {
            $userName = $trail->user?->name ?? 'النظام';
            $alerts[] = [
                'id' => 'audit_' . $trail->id,
                'title' => $trail->action_summary ?: 'إجراء تدقيق وتعديل هيكلي/أكاديمي',
                'description' => "تم بواسطة {$userName} (" . ($trail->event_type ?: 'AUDIT') . ")",
                'time_ago' => $trail->created_at ? $trail->created_at->diffForHumans() : 'مؤخراً',
                'badge_color' => 'bg-emerald-500',
                'target_section' => 'audit',
                'type' => 'AUDIT',
            ];
        }

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'alerts' => $alerts,
        ]);
    }
}
