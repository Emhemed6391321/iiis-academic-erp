<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Branch;
use App\Models\BranchRequest;
use App\Models\BranchRequestTracking;
use App\Models\BranchContract;
use App\Models\BranchAssessment;
use App\Models\Student;
use App\Models\Course;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BranchOperationsController extends Controller
{
    /**
     * Executive Overview of Branch Operations, Requests & Contracts
     */
    public function getOverview(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');

        $requestsQuery = BranchRequest::query();
        $contractsQuery = BranchContract::query();

        if ($branchId && $branchId !== 'all') {
            $requestsQuery->where('branch_id', $branchId);
            $contractsQuery->where('branch_id', $branchId);
        }

        $totalBranches = Branch::where('is_active', true)->count();
        $pendingRequests = (clone $requestsQuery)->whereIn('status', ['pending', 'under_review'])->count();
        $inProgressRequests = (clone $requestsQuery)->where('status', 'in_progress')->count();
        $completedRequests = (clone $requestsQuery)->where('status', 'completed')->count();

        $activeContracts = (clone $contractsQuery)->where('status', 'active')->count();
        $totalContractValue = (clone $contractsQuery)->sum('total_value');

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_branches' => $totalBranches,
                    'pending_requests' => $pendingRequests,
                    'in_progress_requests' => $inProgressRequests,
                    'completed_requests' => $completedRequests,
                    'active_contracts' => $activeContracts,
                    'total_contract_value' => (float)$totalContractValue,
                ]
            ]
        ]);
    }

    /**
     * Get all branches directory with their stats
     */
    public function getBranches(Request $request): JsonResponse
    {
        $branches = Branch::withCount(['students', 'requests', 'contracts'])
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $branches
        ]);
    }

    /**
     * Get Supply, Maintenance & Support Requests
     */
    public function getRequests(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');
        $status = $request->query('status');
        $category = $request->query('category');

        $query = BranchRequest::with(['branch:id,name,code', 'creator:id,name']);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $requests = $query->orderByDesc('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    /**
     * Create a new Branch Support / Maintenance Request
     */
    public function storeRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'category' => 'required|string|max:50',
            'priority' => 'required|in:low,medium,high,urgent',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'target_date' => 'nullable|date',
        ]);

        $ticketNumber = 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $branchRequest = BranchRequest::create([
            'ticket_number' => $ticketNumber,
            'branch_id' => $validated['branch_id'],
            'created_by' => Auth::id() ?? 1,
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'pending',
            'estimated_cost' => $validated['estimated_cost'] ?? 0.00,
            'target_date' => $validated['target_date'] ?? now()->addDays(7)->toDateString(),
        ]);

        BranchRequestTracking::create([
            'request_id' => $branchRequest->id,
            'user_id' => Auth::id() ?? 1,
            'action' => 'إنشاء الطلب',
            'to_status' => 'pending',
            'notes' => 'تم إنشاء طلب الصيانة وتحويله للإدارة المركزية للمتابعة',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال طلب الفرع بنجاح وتوليد رمز التذكرة ' . $ticketNumber,
            'data' => $branchRequest->load('branch:id,name,code')
        ], 201);
    }

    /**
     * Update Request Status (Approve / Complete / Reject)
     */
    public function updateRequestStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,approved,in_progress,completed,rejected',
            'assigned_to' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        $branchRequest = BranchRequest::findOrFail($id);
        $oldStatus = $branchRequest->status;

        $branchRequest->status = $validated['status'];
        if (!empty($validated['assigned_to'])) {
            $branchRequest->assigned_to = $validated['assigned_to'];
        }
        if ($validated['status'] === 'completed') {
            $branchRequest->completed_at = now();
        }
        $branchRequest->save();

        BranchRequestTracking::create([
            'request_id' => $branchRequest->id,
            'user_id' => Auth::id() ?? 1,
            'action' => 'تحديث الحالة إلى ' . $validated['status'],
            'from_status' => $oldStatus,
            'to_status' => $validated['status'],
            'notes' => $validated['notes'] ?? 'تم تحديث حالة التذكرة من الإدارة المركزية للفروع',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الطلب إلى (' . $validated['status'] . ') بنجاح',
            'data' => $branchRequest
        ]);
    }

    /**
     * Get Branch Lease and Maintenance Contracts
     */
    public function getContracts(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');
        $query = BranchContract::with('branch:id,name,code');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $contracts = $query->orderByDesc('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contracts
        ]);
    }

    /**
     * Universal Command Palette & Search (Ctrl + K)
     * Searches students, courses, branches, requests, and navigation screens
     */
    public function universalSearch(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));
        if (empty($q) || mb_strlen($q) < 2) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'students' => [],
                    'courses' => [],
                    'branches' => [],
                    'requests' => [],
                    'screens' => $this->getSystemScreens()
                ]
            ]);
        }

        $user = Auth::user();
        $isGlobal = $user ? $user->hasGlobalAccessScope() : false;
        $userBranchId = $user?->branch_id;

        // 1. Search Students
        $studentsQuery = Student::query();
        if (!$isGlobal && $userBranchId) {
            $studentsQuery->where('branch_id', $userBranchId);
        }
        $students = $studentsQuery->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('family_name', 'like', "%{$q}%")
                    ->orWhere('national_id', 'like', "%{$q}%")
                    ->orWhere('academic_number', 'like', "%{$q}%");
            })
            ->with('branch:id,name,code')
            ->limit(8)
            ->get(['id', 'first_name', 'family_name', 'academic_number', 'national_id', 'branch_id', 'status']);

        // 2. Search Courses
        $courses = Course::where('name', 'like', "%{$q}%")
            ->orWhere('code', 'like', "%{$q}%")
            ->with('department:id,name')
            ->limit(5)
            ->get(['id', 'code', 'name', 'department_id', 'credit_hours']);

        // 3. Search Branches
        $branchesQuery = Branch::query();
        if (!$isGlobal && $userBranchId) {
            $branchesQuery->where('id', $userBranchId);
        }
        $branches = $branchesQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('manager_name', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get(['id', 'code', 'name', 'city', 'manager_name', 'phone']);

        // 4. Search Properties (العقارات)
        $propertiesQuery = \App\Models\Property::query();
        if (!$isGlobal && $userBranchId) {
            $propertiesQuery->where('branch_id', $userBranchId);
        }
        $properties = $propertiesQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('property_number', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            })
            ->with('branch:id,name,code')
            ->limit(5)
            ->get(['id', 'property_number', 'name', 'city', 'property_status', 'branch_id']);

        // 5. Search Contracts (العقود)
        $contractsQuery = \App\Models\BranchContract::query();
        if (!$isGlobal && $userBranchId) {
            $contractsQuery->where('branch_id', $userBranchId);
        }
        $contracts = $contractsQuery->where(function ($query) use ($q) {
                $query->where('contract_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('contractor_name', 'like', "%{$q}%");
            })
            ->with('branch:id,name,code')
            ->limit(5)
            ->get(['id', 'contract_number', 'title', 'contractor_name', 'status', 'branch_id']);

        // 6. Search Requests
        $requestsQuery = BranchRequest::query();
        if (!$isGlobal && $userBranchId) {
            $requestsQuery->where('branch_id', $userBranchId);
        }
        $requests = $requestsQuery->where(function ($query) use ($q) {
                $query->where('ticket_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            })
            ->with('branch:id,name,code')
            ->limit(5)
            ->get(['id', 'ticket_number', 'title', 'category', 'status', 'branch_id']);

        // 7. Matching screens
        $screens = array_values(array_filter($this->getSystemScreens(), function ($s) use ($q) {
            return mb_stripos($s['title'], $q) !== false || mb_stripos($s['category'], $q) !== false || mb_stripos($s['shortcut'], $q) !== false;
        }));

        return response()->json([
            'status' => 'success',
            'data' => [
                'students' => $students,
                'courses' => $courses,
                'branches' => $branches,
                'properties' => $properties,
                'contracts' => $contracts,
                'requests' => $requests,
                'screens' => $screens,
            ]
        ]);
    }

    /**
     * Get Comprehensive Branch Details (Facilities, Classes, Assessments, Staff, Contracts)
     */
    public function getBranchDetails($id): JsonResponse
    {
        $branch = Branch::with([
            'facilities',
            'classes',
            'assessments' => function ($q) {
                $q->orderBy('assessment_date', 'desc')->limit(10);
            },
            'requests' => function ($q) {
                $q->orderBy('id', 'desc')->limit(5);
            },
            'contracts' => function ($q) {
                $q->orderBy('id', 'desc')->limit(5);
            }
        ])
        ->withCount(['students', 'requests', 'contracts', 'classes'])
        ->find($id);

        if (!$branch) {
            return response()->json(['status' => 'error', 'message' => 'الفرع غير موجود'], 404);
        }

        // Calculate seating capacity stats
        $totalCapacity = $branch->classes->sum('max_capacity');
        $enrolledStudents = $branch->students_count;
        $availableSeats = max(0, $totalCapacity - $enrolledStudents);
        $occupancyRate = $totalCapacity > 0 ? round(($enrolledStudents / $totalCapacity) * 100, 1) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'branch' => $branch,
                'stats' => [
                    'total_capacity' => $totalCapacity,
                    'enrolled_students' => $enrolledStudents,
                    'available_seats' => $availableSeats,
                    'occupancy_rate' => $occupancyRate,
                ]
            ]
        ]);
    }

    /**
     * Store a Field Assessment for a Branch
     */
    public function storeAssessment(Request $request, $id): JsonResponse
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['status' => 'error', 'message' => 'الفرع غير موجود'], 404);
        }

        $validated = $request->validate([
            'structure_safety_score' => 'required|integer|min:0|max:20',
            'classrooms_capacity_score' => 'required|integer|min:0|max:20',
            'facilities_hygiene_score' => 'required|integer|min:0|max:20',
            'it_connectivity_score' => 'required|integer|min:0|max:20',
            'admin_compliance_score' => 'required|integer|min:0|max:20',
            'inspector_name' => 'nullable|string|max:150',
            'assessment_date' => 'nullable|date',
            'strengths' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $totalScore = $validated['structure_safety_score']
                    + $validated['classrooms_capacity_score']
                    + $validated['facilities_hygiene_score']
                    + $validated['it_connectivity_score']
                    + $validated['admin_compliance_score'];

        $ratingGrade = match(true) {
            $totalScore >= 95 => 'A+',
            $totalScore >= 85 => 'A',
            $totalScore >= 75 => 'B',
            $totalScore >= 65 => 'C',
            default => 'D'
        };

        $complianceStatus = match(true) {
            $totalScore >= 85 => 'مطابق للمواصفات القياسية',
            $totalScore >= 75 => 'ملاحظات وتجهيزات قيد الاستيفاء',
            default => 'إنذار وتدخل صيانة عاجل'
        };

        $assessment = BranchAssessment::create([
            'branch_id' => $branch->id,
            'inspector_id' => Auth::id(),
            'inspector_name' => $validated['inspector_name'] ?? (Auth::user()?->name ?? 'لجنة التفتيش والمتابعة المركزية'),
            'assessment_date' => $validated['assessment_date'] ?? now()->toDateString(),
            'structure_safety_score' => $validated['structure_safety_score'],
            'classrooms_capacity_score' => $validated['classrooms_capacity_score'],
            'facilities_hygiene_score' => $validated['facilities_hygiene_score'],
            'it_connectivity_score' => $validated['it_connectivity_score'],
            'admin_compliance_score' => $validated['admin_compliance_score'],
            'total_score' => $totalScore,
            'rating_grade' => $ratingGrade,
            'compliance_status' => $complianceStatus,
            'strengths' => $validated['strengths'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update branch summary cache fields
        $branch->update([
            'latest_score' => $totalScore,
            'latest_rating' => $ratingGrade,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ التقييم الميداني بنجاح وتحديث بطاقة الفرع',
            'data' => $assessment
        ]);
    }

    /**
     * Update Branch Geographic Coordinates
     */
    public function updateCoordinates(Request $request, $id): JsonResponse
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['status' => 'error', 'message' => 'الفرع غير موجود'], 404);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $branch->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'geo_location' => "{$validated['latitude']}, {$validated['longitude']}",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الإحداثيات الجغرافية لمقر الفرع بنجاح',
            'data' => $branch
        ]);
    }

    /**
     * Create a new Branch (Section 1)
     */
    public function storeBranch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'short_name' => 'nullable|string|max:50',
            'code' => 'required|string|max:10|unique:branches,code',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'branch_type' => 'nullable|string|max:50',
            'gender_type' => 'nullable|string|in:MALES,FEMALES,COED',
            'branch_status' => 'required|in:ACTIVE,EQUIPPING,SUSPENDED,CLOSED,TRANSFERRED,CANCELED',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'manager_name' => 'nullable|string|max:150',
            'manager_phone' => 'nullable|string|max:50',
            'manager_email' => 'nullable|email|max:100',
            'established_date' => 'nullable|date',
            'operating_date' => 'nullable|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم الفرع مطلوب.',
            'code.required' => 'رمز الفرع مطلوب.',
            'code.unique' => 'رمز الفرع مستخدم مسبقاً.',
            'city.required' => 'المدينة مطلوبة.',
            'branch_status.required' => 'حالة الفرع مطلوبة.',
        ]);

        $validated['is_active'] = $validated['branch_status'] === 'ACTIVE';
        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $validated['geo_location'] = "{$validated['latitude']}, {$validated['longitude']}";
        }

        $branch = Branch::create($validated);

        SystemAuditTrail::log(
            'BRANCH_CREATED',
            "إنشاء فرع جديد: «{$branch->name}» (رمز: {$branch->code}) بمدينة {$branch->city}",
            $branch->toArray(),
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء ملف الفرع بنجاح.',
            'data' => $branch,
        ], 201);
    }

    /**
     * Update Branch Information & Status
     */
    public function updateBranch(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'short_name' => 'nullable|string|max:50',
            'city' => 'sometimes|required|string|max:100',
            'region' => 'nullable|string|max:100',
            'branch_type' => 'nullable|string|max:50',
            'gender_type' => 'nullable|string|in:MALES,FEMALES,COED',
            'branch_status' => 'nullable|in:ACTIVE,EQUIPPING,SUSPENDED,CLOSED,TRANSFERRED,CANCELED',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'manager_name' => 'nullable|string|max:150',
            'manager_phone' => 'nullable|string|max:50',
            'manager_email' => 'nullable|email|max:100',
            'established_date' => 'nullable|date',
            'operating_date' => 'nullable|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['branch_status'])) {
            $validated['is_active'] = $validated['branch_status'] === 'ACTIVE';
        }
        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $validated['geo_location'] = "{$validated['latitude']}, {$validated['longitude']}";
        }

        $oldValues = $branch->toArray();
        $branch->update($validated);

        SystemAuditTrail::log(
            'BRANCH_UPDATED',
            "تحديث بيانات الفرع «{$branch->name}» (رمز: {$branch->code})",
            ['old' => $oldValues, 'new' => $branch->fresh()->toArray()],
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات الفرع بنجاح.',
            'data' => $branch->fresh(),
        ]);
    }

    /**
     * Suspend Branch (إيقاف الفرع مؤقتاً مع سبب الإيقاف)
     */
    public function suspendBranch(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'suspension_reason' => 'required|string|min:4',
        ], [
            'suspension_reason.required' => 'يرجى تدوين سبب ومبررات إيقاف الفرع.',
        ]);

        $branch = Branch::findOrFail($id);

        $branch->update([
            'branch_status' => 'SUSPENDED',
            'is_active' => false,
            'suspended_at' => Carbon::now(),
            'suspension_reason' => $request->suspension_reason,
        ]);

        SystemAuditTrail::log(
            'BRANCH_SUSPENDED',
            "إيقاف الفرع مؤقتاً «{$branch->name}» (سبب: {$request->suspension_reason})",
            ['reason' => $request->suspension_reason, 'suspended_at' => Carbon::now()],
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إيقاف الفرع مؤقتاً وحفظ سبب الإيقاف في السجل.',
            'data' => $branch->fresh(),
        ]);
    }

    /**
     * Activate Branch (إعادة تفعيل الفرع)
     */
    public function activateBranch(int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $branch->update([
            'branch_status' => 'ACTIVE',
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        SystemAuditTrail::log(
            'BRANCH_ACTIVATED',
            "إعادة تفعيل وتنشيط الفرع «{$branch->name}»",
            ['status' => 'ACTIVE'],
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إعادة تفعيل الفرع بنجاح.',
            'data' => $branch->fresh(),
        ]);
    }

    /**
     * Safe Branch Deletion: Strictly check historical data (Section 1.2).
     */
    public function safeDeleteBranch(int $id): JsonResponse
    {
        $branch = Branch::withCount(['students', 'contracts', 'classes', 'requests'])->findOrFail($id);

        $reasons = [];
        if ($branch->students_count > 0) {
            $reasons[] = "{$branch->students_count} طالب مسجل في الفرع";
        }
        if ($branch->contracts_count > 0) {
            $reasons[] = "{$branch->contracts_count} عقود إيجار وتشغيل تاريخية";
        }
        if ($branch->classes_count > 0) {
            $reasons[] = "{$branch->classes_count} شعب وفصول دراسية";
        }
        if ($branch->requests_count > 0) {
            $reasons[] = "{$branch->requests_count} طلبات توريد وصيانة سابقة";
        }

        if (!empty($reasons)) {
            $branch->update(['branch_status' => 'CLOSED', 'is_active' => false]);

            SystemAuditTrail::log(
                'BRANCH_BLOCKED_AND_CLOSED',
                "محاولة حذف الفرع «{$branch->name}» تم منعها لوجود سجلات تاريخية، وتحويل حالته تلقائياً إلى مغلق (CLOSED)",
                ['blocking_reasons' => $reasons],
                Auth::id(),
                $branch->id
            );

            return response()->json([
                'status' => 'error',
                'message' => 'لا يجوز حذف الفرع لوجود بيانات وسجلات تاريخية مرتبطة به: (' . implode('، ', $reasons) . '). تم تعديل حالة الفرع إلى [مغلق/CLOSED] بدلاً من حذفه للحفاظ على تكامل البيانات التاريخية.',
                'action_taken' => 'STATUS_SET_TO_CLOSED',
            ], 422);
        }

        SystemAuditTrail::log(
            'BRANCH_SAFE_DELETED',
            "حذف آمن للفرع «{$branch->name}» لعدم وجود أي ارتباطات",
            $branch->toArray(),
            Auth::id(),
            $branch->id
        );

        $branch->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الفرع لعدم وجود أي ارتباطات تشغيلية أو تاريخية.',
        ]);
    }

    private function getSystemScreens(): array
    {
        return [
            ['title' => 'لوحة القيادة والمؤشرات العامة', 'category' => 'القيادة والتحكم', 'icon' => 'gauge-high', 'action' => 'dashboard', 'shortcut' => 'نظرة عامة'],
            ['title' => 'سجل الطلاب العام وملفات القبول', 'category' => 'شؤون الطلاب', 'icon' => 'user-graduate', 'action' => 'students', 'shortcut' => 'الطلاب'],
            ['title' => 'اعتماد وتدقيق التسجيل (للإدارة العامة)', 'category' => 'شؤون الطلاب', 'icon' => 'user-check', 'action' => 'approvals', 'shortcut' => 'قبول'],
            ['title' => 'شاشات رصد الدرجات والكنترول السريع', 'category' => 'الدراسة والامتحانات', 'icon' => 'table-list', 'action' => 'grades', 'shortcut' => 'رصد'],
            ['title' => 'اعتماد وتدقيق النتائج النهائية والختم الرقمي', 'category' => 'الدراسة والامتحانات', 'icon' => 'clipboard-check', 'action' => 'approvals', 'shortcut' => 'اعتماد'],
            ['title' => 'الأرشيف الرقمي وصحائف الدرجات المعتمدة', 'category' => 'الأرشيف والتوثيق', 'icon' => 'qrcode', 'action' => 'transcripts', 'shortcut' => 'صحائف'],
            ['title' => 'دليل الفروع والمقرات والتقييم', 'category' => 'إدارة الفروع', 'icon' => 'building-columns', 'action' => 'branches_directory', 'shortcut' => 'فروع'],
            ['title' => 'طلبات التوريد والصيانة والتشغيل', 'category' => 'إدارة الفروع', 'icon' => 'screwdriver-wrench', 'action' => 'branch_requests', 'shortcut' => 'تذاكر'],
            ['title' => 'إيجارات المقرات وعقود الصيانة', 'category' => 'إدارة الفروع', 'icon' => 'file-contract', 'action' => 'branch_contracts', 'shortcut' => 'عقود'],
            ['title' => 'مصفوفة الصلاحيات الديناميكية التفاعلية', 'category' => 'إدارة الوصول', 'icon' => 'key', 'action' => 'rbac', 'shortcut' => 'صلاحيات'],
            ['title' => 'سجل التدقيق الجنائي والأمان (Audit Trail)', 'category' => 'الأمان والرقابة', 'icon' => 'shield-halved', 'action' => 'audit', 'shortcut' => 'تدقيق'],
            ['title' => 'الملف الشخصي وإعدادات الأمان 2FA', 'category' => 'المستخدم', 'icon' => 'user-gear', 'action' => 'profile', 'shortcut' => 'ملفي'],
            ['title' => 'الهوية البصرية وتخصيص الثيمات والخطوط', 'category' => 'الإعدادات', 'icon' => 'palette', 'action' => 'themes', 'shortcut' => 'مظهر'],
        ];
    }
}
