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
use App\Models\BranchClass;
use App\Models\BranchFacility;
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

        $totalBranches = Branch::count();
        $activeBranches = Branch::where('is_active', true)->count();
        $ownedBranches = Branch::whereIn('building_type', ['owned', 'state'])->count();
        $rentedBranches = Branch::where('building_type', 'rented')->count();
        $avgScore = round(Branch::avg('latest_score') ?? 0, 1);
        $totalStaff = (int) (Branch::sum('total_staff') ?: (Branch::sum('academic_staff') + Branch::sum('admin_staff')));
        $academicStaff = (int) Branch::sum('academic_staff');
        $adminStaff = (int) Branch::sum('admin_staff');

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
                    'active_branches' => $activeBranches,
                    'owned_branches' => $ownedBranches,
                    'rented_branches' => $rentedBranches,
                    'avg_score' => $avgScore,
                    'total_staff' => $totalStaff,
                    'academic_staff' => $academicStaff,
                    'admin_staff' => $adminStaff,
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
            'building_type' => 'nullable|string|max:50',
            'building_condition' => 'nullable|string|max:50',
            'total_staff' => 'nullable|integer|min:0',
            'academic_staff' => 'nullable|integer|min:0',
            'admin_staff' => 'nullable|integer|min:0',
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
            'facebook_url' => 'nullable|string|max:255',
            'telegram_url' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:50',
            'website_url' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string',
            'social_links' => 'nullable|array',
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
            'code' => 'nullable|string|max:20',
            'city' => 'sometimes|required|string|max:100',
            'region' => 'nullable|string|max:100',
            'branch_type' => 'nullable|string|max:50',
            'gender_type' => 'nullable|string|in:MALES,FEMALES,COED',
            'branch_status' => 'nullable|in:ACTIVE,EQUIPPING,SUSPENDED,CLOSED,TRANSFERRED,CANCELED',
            'building_type' => 'nullable|string|max:50',
            'building_condition' => 'nullable|string|max:50',
            'total_staff' => 'nullable|integer|min:0',
            'academic_staff' => 'nullable|integer|min:0',
            'admin_staff' => 'nullable|integer|min:0',
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
            'facebook_url' => 'nullable|string|max:255',
            'telegram_url' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:50',
            'website_url' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string',
            'social_links' => 'nullable|array',
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
            'message' => 'تم تحديث كافة بيانات الفرع وقنوات التواصل بنجاح.',
            'data' => $branch->fresh()->load(['classes', 'facilities', 'assessments']),
        ]);
    }

    /**
     * Upload a photo for the Branch photo gallery
     */
    public function uploadBranchPhoto(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'caption' => 'nullable|string|max:150',
            'category' => 'nullable|string|max:50',
        ], [
            'photo.required' => 'يرجى اختيار صورة للرفع.',
            'photo.image' => 'الملف المحدد يجب أن يكون صورة صالحة.',
            'photo.max' => 'حجم الصورة لا يجب أن يتجاوز 5 ميجابايت.',
        ]);

        $file = $request->file('photo');
        $filename = 'branch_' . $id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('branches/' . $id, $filename, 'public');
        $url = '/storage/' . $path;

        $photos = $branch->photos ?: [];
        $newPhoto = [
            'url' => $url,
            'caption' => $request->input('caption', 'صورة من مرافق الفرع'),
            'category' => $request->input('category', 'general'),
            'uploaded_at' => now()->toDateTimeString(),
        ];
        $photos[] = $newPhoto;

        // If cover_image is empty, set this as cover image
        $updateData = ['photos' => $photos];
        if (empty($branch->cover_image)) {
            $updateData['cover_image'] = $url;
        }
        $branch->update($updateData);

        SystemAuditTrail::log(
            'BRANCH_PHOTO_UPLOADED',
            "إضافة صورة جديدة لمعرض الفرع «{$branch->name}»: " . ($newPhoto['caption'] ?: 'صورة فرع'),
            $newPhoto,
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم رفع الصورة وإضافتها إلى معرض صور الفرع بنجاح.',
            'photo' => $newPhoto,
            'photos' => $branch->fresh()->photos,
            'branch' => $branch->fresh(),
        ]);
    }

    /**
     * Delete a photo from Branch Gallery
     */
    public function deleteBranchPhoto(Request $request, int $id, int $photoIndex): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $photos = $branch->photos ?: [];

        if (!isset($photos[$photoIndex])) {
            return response()->json(['status' => 'error', 'message' => 'الصورة غير موجودة'], 404);
        }

        $deletedPhoto = $photos[$photoIndex];
        array_splice($photos, $photoIndex, 1);
        $branch->update(['photos' => array_values($photos)]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الصورة من المعرض بنجاح.',
            'photos' => $branch->fresh()->photos,
        ]);
    }

    /**
     * Create Classroom / Hall for Branch
     */
    public function storeBranchClass(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $input = $request->all();
        if (isset($input['equipment']) && is_string($input['equipment'])) {
            $items = array_map('trim', explode('،', str_replace(',', '،', $input['equipment'])));
            $input['equipment'] = array_values(array_filter($items));
            $request->merge(['equipment' => $input['equipment']]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'academic_year' => 'nullable|string|max:50',
            'stage' => 'required|string|max:100',
            'room_type' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'max_capacity' => 'required|integer|min:1|max:500',
            'current_students' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:20',
            'equipment' => 'nullable|array',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم القاعة أو الفصل مطلوب.',
            'stage.required' => 'المرحلة أو الدفعة الدراسية مطلوبة.',
            'max_capacity.required' => 'السعة الاستيعابية القصوى مطلوبة.',
        ]);

        $validated['branch_id'] = $branch->id;
        $validated['academic_year'] = (!empty($validated['academic_year'])) ? $validated['academic_year'] : '2026-2027';
        $validated['room_type'] = (!empty($validated['room_type'])) ? $validated['room_type'] : 'CLASSROOM';
        $validated['current_students'] = $validated['current_students'] ?? 0;
        $validated['available_seats'] = max(0, $validated['max_capacity'] - $validated['current_students']);
        $validated['status'] = (!empty($validated['status'])) ? $validated['status'] : 'active';

        $branchClass = BranchClass::create($validated);

        SystemAuditTrail::log(
            'BRANCH_CLASS_CREATED',
            "إضافة قاعة/فصل جديد «{$branchClass->name}» لفرع «{$branch->name}» بسعة {$branchClass->max_capacity} مقعداً",
            $branchClass->toArray(),
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة القاعة / الفصل بنجاح إلى الفرع.',
            'data' => $branchClass,
        ], 201);
    }

    /**
     * Update Classroom / Hall for Branch
     */
    public function updateBranchClass(Request $request, int $id, int $classId): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $branchClass = BranchClass::where('branch_id', $branch->id)->findOrFail($classId);

        $input = $request->all();
        if (isset($input['equipment']) && is_string($input['equipment'])) {
            $items = array_map('trim', explode('،', str_replace(',', '،', $input['equipment'])));
            $input['equipment'] = array_values(array_filter($items));
            $request->merge(['equipment' => $input['equipment']]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'academic_year' => 'nullable|string|max:50',
            'stage' => 'sometimes|required|string|max:100',
            'room_type' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'max_capacity' => 'sometimes|required|integer|min:1|max:500',
            'current_students' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:20',
            'equipment' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['max_capacity']) || isset($validated['current_students'])) {
            $cap = $validated['max_capacity'] ?? $branchClass->max_capacity;
            $cur = $validated['current_students'] ?? $branchClass->current_students;
            $validated['available_seats'] = max(0, $cap - $cur);
        }

        $branchClass->update($validated);

        SystemAuditTrail::log(
            'BRANCH_CLASS_UPDATED',
            "تحديث بيانات القاعة «{$branchClass->name}» بفرع «{$branch->name}»",
            $branchClass->fresh()->toArray(),
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات القاعة / الفصل بنجاح.',
            'data' => $branchClass->fresh(),
        ]);
    }

    /**
     * Delete Classroom / Hall for Branch
     */
    public function deleteBranchClass(Request $request, int $id, int $classId): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $branchClass = BranchClass::where('branch_id', $branch->id)->findOrFail($classId);

        $className = $branchClass->name;
        $branchClass->delete();

        SystemAuditTrail::log(
            'BRANCH_CLASS_DELETED',
            "حذف القاعة/الفصل «{$className}» من فرع «{$branch->name}»",
            ['branch_id' => $branch->id, 'class_id' => $classId],
            Auth::id(),
            $branch->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف القاعة من سجلات الفرع بنجاح.',
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

    /**
     * Download sample Excel template for bulk Branch Directory Import.
     */
    public function downloadBranchesSampleXlsx(Request $request)
    {
        $headers = [
            'رمز الفرع', 'اسم الفرع', 'الاسم المختصر', 'المدينة', 'المنطقة',
            'نوع الفرع (رئيسي/فرعي)', 'نوع المقر (ملك للدولة/مستأجر)', 'حالة الفرع',
            'اسم المدير', 'هاتف المدير', 'هاتف الفرع', 'البريد الإلكتروني',
            'خط العرض (Latitude)', 'خط الطول (Longitude)', 'الملاحظات'
        ];

        $sampleRows = [
            $headers,
            [
                'TIP-01', 'فرع طرابلس المركزي', 'طرابلس', 'طرابلس', 'المنطقة الغربية',
                'رئيسي', 'ملك للدولة', 'ACTIVE',
                'د. سالم عبد الله المحجوب', '091-2345678', '021-3344556', 'tripoli@iiis.edu.ly',
                32.8872, 13.1913, 'المقر الإداري والأكاديمي الرئيسي'
            ],
            [
                'BEN-02', 'فرع بنغازي التعليمي', 'بنغازي', 'بنغازي', 'المنطقة الشرقية',
                'فرعي', 'ملك للدولة', 'ACTIVE',
                'أ. عثمان محمود البرغثي', '092-3456789', '061-2233445', 'benghazi@iiis.edu.ly',
                32.1167, 20.0667, 'مقر الفرع الإقليمي لشرق ليبيا'
            ],
            [
                'MIS-03', 'فرع مصراتة التعليمي', 'مصراتة', 'مصراتة', 'المنطقة الوسطى',
                'فرعي', 'مستأجر', 'ACTIVE',
                'أ. طارق عبد السلام الفيتوري', '091-8765432', '051-6677889', 'misrata@iiis.edu.ly',
                32.3754, 15.0925, 'فرع مخصص لخدمة المنطقة الوسطى'
            ],
            [
                'ZAW-04', 'فرع الزاوية التعليمي', 'الزاوية', 'الزاوية', 'المنطقة الغربية',
                'فرعي', 'مستأجر', 'ACTIVE',
                'د. فتحي محمد القمودي', '092-9988776', '023-4455667', 'zawiya@iiis.edu.ly',
                32.7522, 12.7278, 'فرع غرب طرابلس'
            ],
            [
                'SEB-05', 'فرع سبها والجنوب', 'سبها', 'سبها', 'المنطقة الجنوبية',
                'فرعي', 'ملك للدولة', 'ACTIVE',
                'الشيخ عبد القادر محمد', '091-5544332', '071-2621122', 'sebha@iiis.edu.ly',
                27.0377, 14.4283, 'فرع فزان والجنوب الليبي الكبير'
            ],
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($sampleRows);
        $filename = 'دليل_الفروع_والمقرات_نموذج_الاستيراد.xlsx';

        return response()->streamDownload(function () use ($xlsx) {
            echo (string) $xlsx;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import multiple Branches from Excel (.xlsx/.xls) or CSV or JSON array.
     */
    public function importBranchesExcel(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح: استيراد الفروع والمقرات محصور بصلاحيات الإدارة العامة فقط.',
            ], 403);
        }

        $rows = [];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->getRealPath();
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext === 'xlsx' || $ext === 'xls') {
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($path)) {
                    $sheetRows = $xlsx->rows();
                    if (count($sheetRows) < 2) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'ملف Excel فارغ أو لا يحتوي على صفوف بيانات.',
                        ], 422);
                    }
                    $headers = array_shift($sheetRows);
                    $headers = array_map(function ($h) {
                        return trim(strtolower(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', (string)$h)));
                    }, $headers);

                    $headerMap = $this->getBranchHeaderMap();

                    foreach ($sheetRows as $lineIndex => $rowValues) {
                        if (empty(array_filter($rowValues, fn($v) => trim((string)$v) !== ''))) {
                            continue;
                        }
                        $rowData = [];
                        foreach ($headers as $idx => $headerName) {
                            $key = $headerMap[$headerName] ?? $headerName;
                            $rowData[$key] = trim((string)($rowValues[$idx] ?? ''));
                        }
                        $rowData['_row_number'] = $lineIndex + 2;
                        $rows[] = $rowData;
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'تعذر قراءة ملف Excel: ' . \Shuchkin\SimpleXLSX::parseError(),
                    ], 422);
                }
            } else {
                // CSV fallback
                $content = file_get_contents($path);
                $bom = pack('H*', 'EFBBBF');
                $content = preg_replace("/^$bom/", '', $content);
                $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
                $lines = array_filter(array_map('trim', $lines));

                if (count($lines) < 2) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'ملف CSV فارغ أو لا يحتوي على صفوف بيانات.',
                    ], 422);
                }

                $delimiter = str_contains($lines[0], ';') ? ';' : (str_contains($lines[0], "\t") ? "\t" : ',');
                $headers = str_getcsv(array_shift($lines), $delimiter);
                $headers = array_map(function ($h) {
                    return trim(strtolower(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $h)));
                }, $headers);
                $headerMap = $this->getBranchHeaderMap();

                foreach ($lines as $lineIndex => $line) {
                    if (empty(trim($line))) continue;
                    $rowValues = str_getcsv($line, $delimiter);
                    $rowData = [];
                    foreach ($headers as $idx => $headerName) {
                        $key = $headerMap[$headerName] ?? $headerName;
                        $rowData[$key] = trim($rowValues[$idx] ?? '');
                    }
                    $rowData['_row_number'] = $lineIndex + 2;
                    $rows[] = $rowData;
                }
            }
        } elseif ($request->has('branches') && is_array($request->branches)) {
            $rows = $request->branches;
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'يرجى إرفاق ملف Excel (.xlsx) أو CSV يحتوي على بيانات الفروع.',
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'status' => 'error',
                'message' => 'لم يتم العثور على أي صفوف صالحة للمعالجة.',
            ], 422);
        }

        $imported = [];
        $updated = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $row['_row_number'] ?? ($index + 1);
            $name = trim($row['name'] ?? $row['branch_name'] ?? '');
            $code = strtoupper(trim($row['code'] ?? $row['branch_code'] ?? ''));
            $city = trim($row['city'] ?? '');
            $region = trim($row['region'] ?? '');
            $managerName = trim($row['manager_name'] ?? '');
            $managerPhone = trim($row['manager_phone'] ?? '');
            $phone = trim($row['phone'] ?? '');
            $email = trim($row['email'] ?? '');
            $notes = trim($row['notes'] ?? '');

            if (empty($name)) {
                $errors[] = [
                    'row' => $rowNum,
                    'code' => $code,
                    'error' => 'اسم الفرع حقل إلزامي لا يمكن تركه فارغاً.',
                ];
                continue;
            }

            if (empty($city)) {
                $city = $this->detectLibyanCity($name) ?: 'طرابلس';
            }

            if (empty($code)) {
                $code = 'BR-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $city), 0, 3) ?: 'GEN') . '-' . str_pad((string)(Branch::count() + 1), 2, '0', STR_PAD_LEFT);
            }

            // Clean building type
            $rawBType = mb_strtolower(trim($row['building_type'] ?? 'state'));
            if (str_contains($rawBType, 'مستأجر') || str_contains($rawBType, 'rented') || str_contains($rawBType, 'ايجار')) {
                $buildingType = 'rented';
            } elseif (str_contains($rawBType, 'ملك') || str_contains($rawBType, 'دولة') || str_contains($rawBType, 'owned') || str_contains($rawBType, 'state')) {
                $buildingType = 'state';
            } else {
                $buildingType = 'state';
            }

            // Clean branch status
            $rawStatus = strtoupper(trim($row['branch_status'] ?? 'ACTIVE'));
            if (in_array($rawStatus, ['ACTIVE', 'نشط', 'مفعل', 'مفتوح'])) {
                $branchStatus = 'ACTIVE';
            } elseif (in_array($rawStatus, ['EQUIPPING', 'قيد التجهيز', 'تجهيز'])) {
                $branchStatus = 'EQUIPPING';
            } elseif (in_array($rawStatus, ['SUSPENDED', 'موقوف', 'معلق'])) {
                $branchStatus = 'SUSPENDED';
            } elseif (in_array($rawStatus, ['CLOSED', 'مغلق', 'مقفل'])) {
                $branchStatus = 'CLOSED';
            } else {
                $branchStatus = 'ACTIVE';
            }

            $lat = !empty($row['latitude']) && is_numeric($row['latitude']) ? floatval($row['latitude']) : null;
            $lng = !empty($row['longitude']) && is_numeric($row['longitude']) ? floatval($row['longitude']) : null;
            $geoLocation = ($lat && $lng) ? "{$lat}, {$lng}" : null;

            try {
                $existing = Branch::where('code', $code)->orWhere('name', $name)->first();

                $branchData = [
                    'name' => $name,
                    'short_name' => trim($row['short_name'] ?? mb_substr($name, 0, 20)),
                    'code' => $code,
                    'city' => $city,
                    'region' => $region ?: $this->detectLibyanRegion($city),
                    'branch_type' => trim($row['branch_type'] ?? 'فرعي'),
                    'building_type' => $buildingType,
                    'branch_status' => $branchStatus,
                    'is_active' => $branchStatus === 'ACTIVE',
                    'manager_name' => $managerName ?: ($existing?->manager_name ?? '—'),
                    'manager_phone' => $managerPhone ?: ($existing?->manager_phone ?? ''),
                    'phone' => $phone ?: ($existing?->phone ?? ''),
                    'email' => $email ?: ($existing?->email ?? ''),
                    'latitude' => $lat ?: $existing?->latitude,
                    'longitude' => $lng ?: $existing?->longitude,
                    'geo_location' => $geoLocation ?: $existing?->geo_location,
                    'notes' => $notes ?: ($existing?->notes ?? 'تم الاستيراد والتحديث عبر ملف إكسل'),
                ];

                if ($existing) {
                    $existing->update($branchData);
                    $updated[] = [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'code' => $existing->code,
                        'city' => $existing->city,
                    ];
                } else {
                    $newBranch = Branch::create($branchData);
                    $imported[] = [
                        'id' => $newBranch->id,
                        'name' => $newBranch->name,
                        'code' => $newBranch->code,
                        'city' => $newBranch->city,
                    ];

                    SystemAuditTrail::log(
                        'BRANCH_CREATED',
                        "إنشاء فرع جديد عبر استيراد إكسل: «{$newBranch->name}» (رمز: {$newBranch->code})",
                        $newBranch->toArray(),
                        $user?->id,
                        $newBranch->id
                    );
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'name' => $name,
                    'code' => $code,
                    'error' => 'خطأ أثناء المعالجة: ' . $e->getMessage(),
                ];
            }
        }

        $totalProcessed = count($imported) + count($updated);
        $failedCount = count($errors);

        return response()->json([
            'status' => $totalProcessed > 0 ? 'success' : 'error',
            'message' => "اكتملت معالجة ملف الفروع: تم استيراد " . count($imported) . " فرعاً جديداً، وتحديث " . count($updated) . " فرعاً قائماً، وفشل {$failedCount} صفوف.",
            'imported_count' => count($imported),
            'updated_count' => count($updated),
            'failed_count' => $failedCount,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ], $totalProcessed > 0 ? 200 : 422);
    }

    private function getBranchHeaderMap(): array
    {
        return [
            'code' => 'code',
            'رمز الفرع' => 'code',
            'كود الفرع' => 'code',
            'name' => 'name',
            'اسم الفرع' => 'name',
            'الفرع' => 'name',
            'short_name' => 'short_name',
            'الاسم المختصر' => 'short_name',
            'city' => 'city',
            'المدينة' => 'city',
            'region' => 'region',
            'المنطقة' => 'region',
            'branch_type' => 'branch_type',
            'نوع الفرع' => 'branch_type',
            'building_type' => 'building_type',
            'نوع المقر' => 'building_type',
            'نوع المبنى' => 'building_type',
            'branch_status' => 'branch_status',
            'حالة الفرع' => 'branch_status',
            'manager_name' => 'manager_name',
            'اسم المدير' => 'manager_name',
            'مدير الفرع' => 'manager_name',
            'manager_phone' => 'manager_phone',
            'هاتف المدير' => 'manager_phone',
            'phone' => 'phone',
            'هاتف الفرع' => 'phone',
            'الهاتف' => 'phone',
            'email' => 'email',
            'البريد الإلكتروني' => 'email',
            'البريد الالكتروني' => 'email',
            'latitude' => 'latitude',
            'خط العرض' => 'latitude',
            'longitude' => 'longitude',
            'خط الطول' => 'longitude',
            'notes' => 'notes',
            'ملاحظات' => 'notes',
            'الملاحظات' => 'notes',
        ];
    }

    private function detectLibyanCity(string $text): string
    {
        $cities = [
            'طرابلس', 'بنغازي', 'مصراتة', 'الزاوية', 'سبها', 'زليتن', 'طبرق', 'البيضاء',
            'غريان', 'سرت', 'درنة', 'الخمس', 'ترهونة', 'صبراتة', 'اجدابيا', 'الكفرة',
            'يفرن', 'غدامس', 'نالوت', 'بني وليد', 'تاجوراء', 'جنزور'
        ];
        foreach ($cities as $c) {
            if (mb_strpos($text, $c) !== false) {
                return $c;
            }
        }
        return 'طرابلس';
    }

    private function detectLibyanRegion(string $city): string
    {
        $west = ['طرابلس', 'الزاوية', 'غريان', 'صبراتة', 'الخمس', 'ترهونة', 'يفرن', 'نالوت', 'تاجوراء', 'جنزور', 'زوارة'];
        $east = ['بنغازي', 'طبرق', 'البيضاء', 'درنة', 'اجدابيا', 'المرج', 'شحات'];
        $south = ['سبها', 'الكفرة', 'غدامس', 'مرزق', 'أوباري', 'غات'];
        $middle = ['مصراتة', 'زليتن', 'سرت', 'بني وليد', 'الجفرة'];

        if (in_array($city, $west)) return 'المنطقة الغربية';
        if (in_array($city, $east)) return 'المنطقة الشرقية';
        if (in_array($city, $south)) return 'المنطقة الجنوبية';
        if (in_array($city, $middle)) return 'المنطقة الوسطى';
        return 'المنطقة الغربية';
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
