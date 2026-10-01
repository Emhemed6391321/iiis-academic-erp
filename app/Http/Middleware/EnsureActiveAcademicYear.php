<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureActiveAcademicYear
 *
 * يمنع أي عمليات إنشاء/تعديل إذا لم يكن هناك عام دراسي نشط ومعتمد.
 * يُستخدم على endpoints إضافة الطلاب، إدخال الدرجات، وإنشاء المقررات.
 *
 * الاستخدام في routes: ->middleware('academic.year.active')
 */
class EnsureActiveAcademicYear
{
    public function handle(Request $request, Closure $next): Response
    {
        $activeYear = AcademicYear::where('is_current', true)
                                  ->where('is_locked', false)
                                  ->first();

        if (!$activeYear) {
            return response()->json([
                'success' => false,
                'error_code' => 'NO_ACTIVE_ACADEMIC_YEAR',
                'message' => 'لا يمكن تنفيذ هذه العملية: لا يوجد عام دراسي نشط ومعتمد في النظام.',
                'detail'  => 'يرجى مراجعة الإدارة العامة لتفعيل العام الدراسي الجديد قبل إجراء أي تسجيلات أو إدخالات.',
                'hint'    => 'قم بالانتقال إلى قسم الإعدادات > التقويم الأكاديمي > تفعيل العام الدراسي.',
            ], 423); // 423 Locked
        }

        // نُضيف العام النشط في request attributes لاستخدامه في Controllers
        $request->attributes->set('active_academic_year', $activeYear);
        $request->attributes->set('active_academic_year_id', $activeYear->id);

        return $next($request);
    }
}
