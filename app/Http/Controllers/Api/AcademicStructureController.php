<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchClass;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudyYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AcademicStructureController extends Controller
{
    /**
     * نظرة عامة وإحصائيات الهيكل الأكاديمي والمراحل والشعب حسب العام الدراسي
     */
    public function getOverview(Request $request): JsonResponse
    {
        $yearId = $request->query('academic_year_id');
        $academicYear = null;

        if ($yearId) {
            $academicYear = AcademicYear::find($yearId);
        }
        if (!$academicYear) {
            $academicYear = AcademicYear::where('is_current', true)->first()
                ?: AcademicYear::orderBy('id', 'desc')->first();
        }

        $user = $request->user() ?: Auth::user();
        $isGlobal = $user ? $user->hasGlobalAccessScope() : true;
        $userBranchId = $user?->branch_id;

        $allAcademicYears = AcademicYear::orderBy('id', 'desc')->get();
        $branchesQuery = Branch::orderBy('name', 'asc');
        if (!$isGlobal && $userBranchId) {
            $branchesQuery->where('id', $userBranchId);
        }
        $branches = $branchesQuery->get();

        // 1. المراحل والسنوات الدراسية مع إحصائيات الطلاب والمقررات
        $studyYears = StudyYear::orderBy('level_order', 'asc')->get()->map(function ($sy) use ($academicYear, $isGlobal, $userBranchId) {
            $studentsQuery = Student::where('current_study_year_id', $sy->id);
            if (!$isGlobal && $userBranchId) {
                $studentsQuery->where('branch_id', $userBranchId);
            }
            $studentsCount = $studentsQuery->count();
            $coursesCount = Course::where('study_year_id', $sy->id)
                ->when($academicYear, function ($q) use ($academicYear) {
                    $q->where('academic_year_id', $academicYear->id);
                })
                ->count();

            return [
                'id'             => $sy->id,
                'name'           => $sy->name,
                'level_order'    => $sy->level_order,
                'description'    => $sy->description,
                'students_count' => $studentsCount,
                'courses_count'  => $coursesCount,
                'created_at'     => $sy->created_at?->format('Y-m-d'),
            ];
        });

        // 2. الأقسام والشُعب التخصصية مع إحصائيات المقررات والطلاب
        $departments = Department::orderBy('id', 'asc')->get()->map(function ($dept) use ($academicYear, $isGlobal, $userBranchId) {
            $studentsQuery = Student::where('department_id', $dept->id);
            if (!$isGlobal && $userBranchId) {
                $studentsQuery->where('branch_id', $userBranchId);
            }
            $studentsCount = $studentsQuery->count();
            $coursesCount = Course::where('department_id', $dept->id)
                ->when($academicYear, function ($q) use ($academicYear) {
                    $q->where('academic_year_id', $academicYear->id);
                })
                ->count();

            return [
                'id'             => $dept->id,
                'code'           => $dept->code,
                'name'           => $dept->name,
                'description'    => $dept->description,
                'is_active'      => (bool) $dept->is_active,
                'students_count' => $studentsCount,
                'courses_count'  => $coursesCount,
                'created_at'     => $dept->created_at?->format('Y-m-d'),
            ];
        });

        // 3. الفصول والشُعب الدراسية بالفروع مرتبطة بالعام الدراسي
        $classesQuery = BranchClass::with('branch');
        if (!$isGlobal && $userBranchId) {
            $classesQuery->where('branch_id', $userBranchId);
        }
        if ($academicYear) {
            $yearCode = $academicYear->code;
            $classesQuery->where(function ($q) use ($yearCode) {
                $q->where('academic_year', $yearCode)
                  ->orWhere('academic_year', 'like', "%{$yearCode}%");
            });
        }
        $branchClasses = $classesQuery->orderBy('id', 'desc')->get()->map(function ($c) {
            $available = max(0, $c->max_capacity - $c->current_students);
            $occupancyRate = $c->max_capacity > 0 ? round(($c->current_students / $c->max_capacity) * 100, 1) : 0;

            return [
                'id'               => $c->id,
                'branch_id'        => $c->branch_id,
                'branch_name'      => $c->branch?->name ?: 'غير محدد',
                'name'             => $c->name,
                'academic_year'    => $c->academic_year,
                'stage'            => $c->stage,
                'max_capacity'     => $c->max_capacity,
                'current_students' => $c->current_students,
                'available_seats'  => $available,
                'occupancy_rate'   => $occupancyRate,
                'status'           => $c->status,
                'notes'            => $c->notes,
                'created_at'       => $c->created_at?->format('Y-m-d'),
            ];
        });

        // KPIs
        $totalStages = $studyYears->count();
        $totalDepts = $departments->where('is_active', true)->count();
        $totalClasses = $branchClasses->count();
        $totalCapacity = $branchClasses->sum('max_capacity');
        $totalCurrent = $branchClasses->sum('current_students');
        $totalAvailable = max(0, $totalCapacity - $totalCurrent);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'selected_year'   => $academicYear,
                'academic_years'  => $allAcademicYears,
                'branches'        => $branches,
                'study_years'     => $studyYears,
                'departments'     => $departments,
                'branch_classes'  => $branchClasses,
                'kpis'            => [
                    'total_stages'     => $totalStages,
                    'active_depts'     => $totalDepts,
                    'total_classes'    => $totalClasses,
                    'total_capacity'   => $totalCapacity,
                    'total_enrolled'   => $totalCurrent,
                    'available_seats'  => $totalAvailable,
                ]
            ]
        ]);
    }

    // =========================================================================
    // 1. المراحل والسنوات الدراسية (Study Years CRUD)
    // =========================================================================

    public function storeStudyYear(Request $request): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: إدارة وتعديل المراحل والسنوات الدراسية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:50',
            'level_order' => 'required|integer|min:1|max:255|unique:study_years,level_order',
            'description' => 'nullable|string|max:150',
        ], [
            'name.required'        => 'اسم المرحلة الدراسية مطلوب.',
            'level_order.required' => 'الترتيب الإداري مطلوب.',
            'level_order.unique'   => 'رقم الترتيب مستخدم مسبقاً لمرحلة أخرى.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $studyYear = StudyYear::create([
            'name'        => $request->name,
            'level_order' => $request->level_order,
            'description' => $request->description,
        ]);

        $this->logAudit('STUDY_YEAR_CREATED', "إضافة مرحلة دراسية: {$studyYear->name} (ترتيب: {$studyYear->level_order})", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تمت إضافة المرحلة الدراسية «{$studyYear->name}» بنجاح.",
            'data'    => $studyYear,
        ], 201);
    }

    public function updateStudyYear(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: إدارة وتعديل المراحل والسنوات الدراسية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $studyYear = StudyYear::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:50',
            'level_order' => 'required|integer|min:1|max:255|unique:study_years,level_order,' . $studyYear->id,
            'description' => 'nullable|string|max:150',
        ], [
            'name.required'        => 'اسم المرحلة الدراسية مطلوب.',
            'level_order.unique'   => 'رقم الترتيب مستخدم مسبقاً لمرحلة أخرى.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $studyYear->update([
            'name'        => $request->name,
            'level_order' => $request->level_order,
            'description' => $request->description,
        ]);

        $this->logAudit('STUDY_YEAR_UPDATED', "تعديل مرحلة دراسية: {$studyYear->name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تم تحديث بيانات المرحلة «{$studyYear->name}» بنجاح.",
            'data'    => $studyYear,
        ]);
    }

    public function destroyStudyYear(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: إدارة وحذف المراحل والسنوات الدراسية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $studyYear = StudyYear::findOrFail($id);

        // حماية من الحذف إذا كان هناك طلاب مقيدين
        $studentsCount = Student::where('current_study_year_id', $studyYear->id)->count();
        if ($studentsCount > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "❌ لا يمكن حذف المرحلة الدراسية «{$studyYear->name}» لوجود ({$studentsCount}) طالب مسجلين بها حالياً.",
            ], 422);
        }

        // حماية إذا كانت هناك مقررات دراسية مرتبطة
        $coursesCount = Course::where('study_year_id', $studyYear->id)->count();
        if ($coursesCount > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "❌ لا يمكن حذف المرحلة الدراسية لوجود ({$coursesCount}) مقرر دراسي معتمد مرتبط بها.",
            ], 422);
        }

        $name = $studyYear->name;
        $studyYear->delete();

        $this->logAudit('STUDY_YEAR_DELETED', "حذف مرحلة دراسية: {$name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "🗑️ تم حذف المرحلة الدراسية «{$name}» بنجاح.",
        ]);
    }

    // =========================================================================
    // 2. الأقسام والشُعب التخصصية (Departments CRUD)
    // =========================================================================

    public function storeDepartment(Request $request): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: إدارة وتعديل الأقسام والشُعب العلمية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'code'        => 'required|string|max:20|unique:departments,code',
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ], [
            'code.required' => 'رمز القسم/الشعبة مطلوب.',
            'code.unique'   => 'رمز القسم مستخدم مسبقاً.',
            'name.required' => 'اسم القسم/الشعبة مطلوب.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $dept = Department::create([
            'code'        => strtoupper($request->code),
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        $this->logAudit('DEPARTMENT_CREATED', "إضافة قسم/شعبة علمية: {$dept->name} (كود: {$dept->code})", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تمت إضافة القسم/الشعبة «{$dept->name}» بنجاح.",
            'data'    => $dept,
        ], 201);
    }

    public function updateDepartment(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: إدارة وتعديل الأقسام والشُعب العلمية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $dept = Department::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code'        => 'required|string|max:20|unique:departments,code,' . $dept->id,
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ], [
            'code.required' => 'رمز القسم مطلوب.',
            'code.unique'   => 'رمز القسم مستخدم مسبقاً.',
            'name.required' => 'اسم القسم مطلوب.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $dept->update([
            'code'        => strtoupper($request->code),
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', $dept->is_active),
        ]);

        $this->logAudit('DEPARTMENT_UPDATED', "تعديل قسم/شعبة: {$dept->name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تم تحديث بيانات القسم «{$dept->name}» بنجاح.",
            'data'    => $dept,
        ]);
    }

    public function toggleDepartmentStatus(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: تعديل حالة الأقسام والشُعب العلمية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $dept = Department::findOrFail($id);
        $dept->is_active = !$dept->is_active;
        $dept->save();

        $statusText = $dept->is_active ? 'تفعيل' : 'تعطيل';
        $this->logAudit('DEPARTMENT_STATUS_TOGGLED', "{$statusText} القسم/الشعبة: {$dept->name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "تم {$statusText} القسم «{$dept->name}» بنجاح.",
            'data'    => $dept,
        ]);
    }

    public function destroyDepartment(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        if (!$user || !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: حذف الأقسام والشُعب العلمية محصورة بالإدارة العامة المركزية فقط.'], 403);
        }

        $dept = Department::findOrFail($id);

        $studentsCount = Student::where('department_id', $dept->id)->count();
        if ($studentsCount > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "❌ لا يمكن حذف القسم «{$dept->name}» لوجود ({$studentsCount}) طالب مسجلين تحت تخصصه.",
            ], 422);
        }

        $coursesCount = Course::where('department_id', $dept->id)->count();
        if ($coursesCount > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "❌ لا يمكن حذف القسم لوجود ({$coursesCount}) مقرر دراسي مرتبط به باللائحة.",
            ], 422);
        }

        $name = $dept->name;
        $dept->delete();

        $this->logAudit('DEPARTMENT_DELETED', "حذف قسم/شعبة: {$name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "🗑️ تم حذف القسم «{$name}» بنجاح.",
        ]);
    }

    // =========================================================================
    // 3. شُعب وفصول الفروع حسب العام الدراسي (Branch Classes CRUD)
    // =========================================================================

    public function storeBranchClass(Request $request): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $isGlobal = $user ? $user->hasGlobalAccessScope() : true;
        if (!$isGlobal && $user?->branch_id) {
            $request->merge(['branch_id' => $user->branch_id]);
        }

        $validator = Validator::make($request->all(), [
            'branch_id'     => 'required|exists:branches,id',
            'name'          => 'required|string|max:100',
            'academic_year' => 'required|string|max:50',
            'stage'         => 'required|string|max:100',
            'max_capacity'  => 'required|integer|min:1|max:500',
            'status'        => 'nullable|string|in:active,full,maintenance,inactive',
            'notes'         => 'nullable|string|max:500',
        ], [
            'branch_id.required'     => 'الفرع/المعهد مطلوب.',
            'name.required'          => 'اسم الفصل/القاعة مطلوب.',
            'academic_year.required' => 'العام الدراسي مطلوب.',
            'stage.required'         => 'المرحلة الدراسية مطلوبة.',
            'max_capacity.required'  => 'السعة الاستيعابية مطلوبة.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $branchId = (!$isGlobal && $user?->branch_id) ? $user->branch_id : $request->branch_id;
        $capacity = (int) $request->max_capacity;
        $class = BranchClass::create([
            'branch_id'        => $branchId,
            'name'             => $request->name,
            'academic_year'    => $request->academic_year,
            'stage'            => $request->stage,
            'max_capacity'     => $capacity,
            'current_students' => 0,
            'available_seats'  => $capacity,
            'status'           => $request->status ?: 'active',
            'notes'            => $request->notes,
        ]);

        $this->logAudit('BRANCH_CLASS_CREATED', "إضافة فصل/شعبة دراسية: {$class->name} بالفرع ID: {$class->branch_id} للعام: {$class->academic_year}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تمت إضافة الفصل/الشعبة «{$class->name}» بنجاح.",
            'data'    => $class,
        ], 201);
    }

    public function updateBranchClass(Request $request, $id): JsonResponse
    {
        $class = BranchClass::findOrFail($id);

        $user = $request->user() ?: Auth::user();
        $isGlobal = $user ? $user->hasGlobalAccessScope() : true;
        if (!$isGlobal && $user?->branch_id && $class->branch_id != $user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: لا يمكنك تعديل فصول وقاعات فروع أخرى.'], 403);
        }

        if (!$isGlobal && $user?->branch_id) {
            $request->merge(['branch_id' => $user->branch_id]);
        }

        $validator = Validator::make($request->all(), [
            'branch_id'     => 'required|exists:branches,id',
            'name'          => 'required|string|max:100',
            'academic_year' => 'required|string|max:50',
            'stage'         => 'required|string|max:100',
            'max_capacity'  => 'required|integer|min:1|max:500',
            'status'        => 'nullable|string|in:active,full,maintenance,inactive',
            'notes'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $branchId = (!$isGlobal && $user?->branch_id) ? $user->branch_id : $request->branch_id;
        $maxCapacity = (int) $request->max_capacity;
        $current = $class->current_students;
        $available = max(0, $maxCapacity - $current);

        $class->update([
            'branch_id'        => $branchId,
            'name'             => $request->name,
            'academic_year'    => $request->academic_year,
            'stage'            => $request->stage,
            'max_capacity'     => $maxCapacity,
            'available_seats'  => $available,
            'status'           => $request->status ?: $class->status,
            'notes'            => $request->notes,
        ]);

        $this->logAudit('BRANCH_CLASS_UPDATED', "تعديل فصل/شعبة: {$class->name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تم تحديث بيانات الفصل/الشعبة «{$class->name}» بنجاح.",
            'data'    => $class,
        ]);
    }

    public function destroyBranchClass(Request $request, $id): JsonResponse
    {
        $class = BranchClass::findOrFail($id);

        $user = $request->user() ?: Auth::user();
        $isGlobal = $user ? $user->hasGlobalAccessScope() : true;
        if (!$isGlobal && $user?->branch_id && $class->branch_id != $user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح: لا يمكنك حذف فصول وقاعات فروع أخرى.'], 403);
        }

        if ($class->current_students > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "❌ لا يمكن حذف الفصل لوجود ({$class->current_students}) طالب مقيدين به حالياً.",
            ], 422);
        }

        $name = $class->name;
        $class->delete();

        $this->logAudit('BRANCH_CLASS_DELETED', "حذف فصل/شعبة: {$name}", $request);

        return response()->json([
            'status'  => 'success',
            'message' => "🗑️ تم حذف الفصل/الشعبة «{$name}» بنجاح.",
        ]);
    }

    /**
     * Helper to log audit trail
     */
    private function logAudit(string $eventType, string $description, Request $request): void
    {
        try {
            DB::table('system_audit_trails')->insert([
                'user_id'     => Auth::id(),
                'event_type'  => $eventType,
                'description' => $description,
                'ip_address'  => $request->ip(),
                'payload'     => json_encode($request->except(['password', '_token'])),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {}
    }
}
