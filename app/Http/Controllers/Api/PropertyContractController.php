<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\Property;
use App\Models\ContractInstallment;
use App\Models\SystemAuditTrail;
use App\Services\ContractFinancialEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PropertyContractController extends Controller
{
    public function __construct(
        protected ContractFinancialEngineService $financialEngine
    ) {}

    // =========================================================================
    // 1. PROPERTIES MANAGEMENT (إدارة العقارات والمقرات)
    // =========================================================================

    public function listProperties(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Property::with(['branch:id,name,code', 'activeContract']);

        // Branch scoping
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('status')) {
            $query->where('property_status', $request->status);
        }
        if ($request->filled('usage')) {
            $query->where('usage_status', $request->usage);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('property_number', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%")
                  ->orWhere('owner_name', 'like', "%{$s}%");
            });
        }

        $properties = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $properties,
        ]);
    }

    public function storeProperty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|string|max:100',
            'property_number' => 'nullable|string|max:50|unique:properties,property_number',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'area_sqm' => 'nullable|numeric|min:0',
            'floors_count' => 'nullable|integer|min:1',
            'halls_count' => 'nullable|integer|min:0',
            'offices_count' => 'nullable|integer|min:0',
            'bathrooms_count' => 'nullable|integer|min:0',
            'yards_count' => 'nullable|integer|min:0',
            'labs_count' => 'nullable|integer|min:0',
            'has_library' => 'nullable|boolean',
            'has_mosque' => 'nullable|boolean',
            'storage_count' => 'nullable|integer|min:0',
            'parking_capacity' => 'nullable|integer|min:0',
            'owner_name' => 'nullable|string|max:200',
            'owner_contact' => 'nullable|string|max:100',
            'property_status' => 'required|in:READY,MAINTENANCE,UNDER_CONSTRUCTION',
            'usage_status' => 'required|in:OCCUPIED,VACANT,PARTIAL',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم/وصف العقار مطلوب.',
            'city.required' => 'المدينة مطلوبة.',
            'property_status.required' => 'حالة العقار مطلوبة.',
        ]);

        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id) {
            $validated['branch_id'] = $user->branch_id;
        }

        if (empty($validated['property_number'])) {
            $validated['property_number'] = 'PROP-' . date('Y') . '-' . str_pad((string)(Property::count() + 1), 4, '0', STR_PAD_LEFT);
        }

        $property = Property::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل بيانات العقار/المقر بنجاح.',
            'data' => $property->load('branch'),
        ], 201);
    }

    public function showProperty(int $id): JsonResponse
    {
        $property = Property::with(['branch', 'contracts.installments', 'activeContract'])->findOrFail($id);
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id) && $property->branch_id && (int)$property->branch_id !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح باستعراض بيانات عقار يتبع فرعاً آخر.'], 403);
        }

        $financial = $this->financialEngine->getPropertyFinancialStatement($property);

        return response()->json([
            'status' => 'success',
            'data' => [
                'property' => $property,
                'financial' => $financial,
            ]
        ]);
    }

    public function updateProperty(Request $request, int $id): JsonResponse
    {
        $property = Property::findOrFail($id);

        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id && (int)$property->branch_id !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح بتعديل عقار يتبع فرعاً آخر.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:200',
            'type' => 'sometimes|required|string|max:100',
            'city' => 'sometimes|required|string|max:100',
            'region' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'area_sqm' => 'nullable|numeric|min:0',
            'floors_count' => 'nullable|integer|min:1',
            'halls_count' => 'nullable|integer|min:0',
            'offices_count' => 'nullable|integer|min:0',
            'bathrooms_count' => 'nullable|integer|min:0',
            'yards_count' => 'nullable|integer|min:0',
            'labs_count' => 'nullable|integer|min:0',
            'has_library' => 'nullable|boolean',
            'has_mosque' => 'nullable|boolean',
            'storage_count' => 'nullable|integer|min:0',
            'parking_capacity' => 'nullable|integer|min:0',
            'owner_name' => 'nullable|string|max:200',
            'owner_contact' => 'nullable|string|max:100',
            'property_status' => 'nullable|in:READY,MAINTENANCE,UNDER_CONSTRUCTION',
            'usage_status' => 'nullable|in:OCCUPIED,VACANT,PARTIAL',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $property->toArray();
        $property->update($validated);

        SystemAuditTrail::log(
            'PROPERTY_UPDATED',
            "تحديث بيانات العقار «{$property->name}» (رقم: {$property->property_number})",
            ['old' => $oldValues, 'new' => $property->fresh()->toArray()]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات العقار بنجاح.',
            'data' => $property->fresh()->load('branch'),
        ]);
    }

    public function deleteProperty(int $id): JsonResponse
    {
        $property = Property::withCount('contracts')->findOrFail($id);

        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id && (int)$property->branch_id !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح بحذف عقار يتبع فرعاً آخر.'], 403);
        }

        if ($property->contracts_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "لا يمكن حذف هذا العقار لوجود {$property->contracts_count} عقود تاريخية مرتبطة به. يمكن تغيير حالته إلى غير مستخدم بدلاً من الحذف.",
            ], 422);
        }

        SystemAuditTrail::log(
            'PROPERTY_DELETED',
            "حذف العقار «{$property->name}» (رقم: {$property->property_number})",
            $property->toArray()
        );

        $property->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف العقار بنجاح.',
        ]);
    }

    // =========================================================================
    // 2. CONTRACTS MANAGEMENT & LIFECYCLE (نظام وإدارة دورة حياة العقود)
    // =========================================================================

    public function listContracts(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = BranchContract::with(['branch:id,name,code', 'property:id,name,property_number,city', 'createdBy:id,name', 'approvedBy:id,name']);

        // Branch scoping
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('contract_number', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%")
                  ->orWhere('contractor_name', 'like', "%{$s}%")
                  ->orWhere('lessor_name', 'like', "%{$s}%");
            });
        }

        $contracts = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contracts,
        ]);
    }

    public function storeContract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'property_id' => 'nullable|exists:properties,id',
            'contractor_name' => 'required|string|max:200',
            'contractor_phone' => 'nullable|string|max:50',
            'lessor_name' => 'nullable|string|max:200',
            'lessee_name' => 'nullable|string|max:200',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'duration_months' => 'nullable|integer|min:1',
            'total_value' => 'required|numeric|min:0',
            'rent_amount' => 'nullable|numeric|min:0',
            'payment_frequency' => 'required|in:MONTHLY,QUARTERLY,SEMI_ANNUAL,ANNUAL',
            'installment_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:100',
            'due_day' => 'nullable|integer|between:1,31',
            'annual_increase_percentage' => 'nullable|numeric|min:0|max:100',
            'grace_period_days' => 'nullable|integer|min:0',
            'renewal_terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'title.required' => 'عنوان العقد مطلوب.',
            'branch_id.required' => 'يرجى تحديد الفرع المستفيد.',
            'start_date.required' => 'تاريخ بدء السريان مطلوب.',
            'end_date.required' => 'تاريخ انتهاء السريان مطلوب.',
            'total_value.required' => 'قيمة العقد الإجمالية مطلوبة.',
        ]);

        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id && (int)$validated['branch_id'] !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح بإنشاء عقد لفرع آخر.'], 403);
        }

        $validated['created_by_id'] = $user?->id;
        $validated['status'] = 'DRAFT'; // Initial lifecycle status: مسودة
        $validated['contract_number'] = 'CNT-' . date('Y') . '-' . str_pad((string)(BranchContract::count() + 1), 4, '0', STR_PAD_LEFT);

        if (empty($validated['rent_amount'])) {
            $validated['rent_amount'] = $validated['total_value'];
        }
        if (empty($validated['installment_amount'])) {
            $months = $validated['duration_months'] ?? 12;
            $validated['installment_amount'] = $months > 0 ? round($validated['total_value'] / $months, 2) : $validated['total_value'];
        }

        $contract = BranchContract::create($validated);

        // Generate preliminary installment schedule
        $this->generateInstallmentsSchedule($contract);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء مسودة العقد وجدول الدفعات بنجاح.',
            'data' => $contract->load(['branch', 'property', 'installments']),
        ], 201);
    }

    public function showContract(int $id): JsonResponse
    {
        $contract = BranchContract::with([
            'branch',
            'property',
            'installments',
            'createdBy',
            'approvedBy',
            'suspendedBy',
            'terminatedBy'
        ])->findOrFail($id);

        $this->authorizeContractAccess($contract, Auth::user());

        return response()->json([
            'status' => 'success',
            'data' => $contract,
        ]);
    }

    /**
     * Approve contract: Transition to APPROVED / ACTIVE.
     */
    public function approveContract(int $id): JsonResponse
    {
        $contract = BranchContract::findOrFail($id);
        $user = Auth::user();

        $this->authorizeContractAccess($contract, $user);

        if (in_array(strtoupper($contract->status), ['ACTIVE', 'APPROVED'])) {
            return response()->json(['status' => 'error', 'message' => 'العقد معتمد بالفعل.'], 422);
        }

        $oldStatus = $contract->status;
        $contract->update([
            'status' => 'ACTIVE',
            'approved_by_id' => $user?->id,
            'approved_at' => Carbon::now(),
        ]);

        SystemAuditTrail::log(
            'CONTRACT_APPROVED',
            "اعتماد وسريان العقد «{$contract->title}» (رقم: {$contract->contract_number})",
            ['old_status' => $oldStatus, 'new_status' => 'ACTIVE', 'contract_id' => $contract->id],
            $user?->id,
            $contract->branch_id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم اعتماد وسريان العقد رسمياً.',
            'data' => $contract->fresh(),
        ]);
    }

    /**
     * Suspend contract (إيقاف العقد مؤقتاً).
     */
    public function suspendContract(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'suspension_reason' => 'required|string|min:4',
            'suspension_document' => 'nullable|string',
        ], [
            'suspension_reason.required' => 'سبب ومبرر إيقاف العقد مطلوب.',
        ]);

        $contract = BranchContract::findOrFail($id);
        $user = Auth::user();

        $this->authorizeContractAccess($contract, $user);

        $oldStatus = $contract->status;
        $contract->update([
            'status' => 'SUSPENDED',
            'suspended_at' => Carbon::now(),
            'suspension_reason' => $request->suspension_reason,
            'suspension_document' => $request->suspension_document,
            'suspended_by_id' => $user?->id,
        ]);

        SystemAuditTrail::log(
            'CONTRACT_SUSPENDED',
            "إيقاف العقد مؤقتاً «{$contract->title}» (رقم: {$contract->contract_number}) بسبب: {$request->suspension_reason}",
            [
                'old_status' => $oldStatus,
                'new_status' => 'SUSPENDED',
                'contract_id' => $contract->id,
                'reason' => $request->suspension_reason,
                'document' => $request->suspension_document
            ],
            $user?->id,
            $contract->branch_id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إيقاف العقد مؤقتاً وحفظ قرار ومبررات الإيقاف.',
            'data' => $contract->fresh(),
        ]);
    }

    /**
     * Reactivate suspended contract.
     */
    public function activateContract(int $id): JsonResponse
    {
        $contract = BranchContract::findOrFail($id);
        $user = Auth::user();

        $this->authorizeContractAccess($contract, $user);

        if (strtoupper($contract->status) !== 'SUSPENDED') {
            return response()->json(['status' => 'error', 'message' => 'العقد ليس في حالة إيقاف مؤقت.'], 422);
        }

        $oldStatus = $contract->status;
        $contract->update([
            'status' => 'ACTIVE',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        SystemAuditTrail::log(
            'CONTRACT_REACTIVATED',
            "إعادة تفعيل وسريان العقد «{$contract->title}» (رقم: {$contract->contract_number})",
            ['old_status' => $oldStatus, 'new_status' => 'ACTIVE', 'contract_id' => $contract->id],
            $user?->id,
            $contract->branch_id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إعادة تفعيل وسريان العقد بنجاح.',
            'data' => $contract->fresh(),
        ]);
    }

    /**
     * Terminate contract (إنهاء العقد).
     */
    public function terminateContract(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'termination_reason' => 'required|string|min:4',
            'remaining_obligations' => 'nullable|numeric|min:0',
            'property_status_after_termination' => 'nullable|string',
        ], [
            'termination_reason.required' => 'سبب إنهاء العقد مطلوب.',
        ]);

        $contract = BranchContract::findOrFail($id);
        $user = Auth::user();

        $this->authorizeContractAccess($contract, $user);

        $oldStatus = $contract->status;
        $contract->update([
            'status' => 'TERMINATED',
            'terminated_at' => Carbon::now(),
            'termination_reason' => $request->termination_reason,
            'terminated_by_id' => $user?->id,
            'remaining_obligations' => $request->remaining_obligations ?? 0.00,
            'property_status_after_termination' => $request->property_status_after_termination ?? 'VACANT',
        ]);

        // If linked to a property, update property usage if needed
        if ($contract->property_id && $request->property_status_after_termination) {
            $property = Property::find($contract->property_id);
            if ($property) {
                $property->update(['usage_status' => $request->property_status_after_termination]);
            }
        }

        SystemAuditTrail::log(
            'CONTRACT_TERMINATED',
            "إنهاء العقد رسمياً «{$contract->title}» (رقم: {$contract->contract_number}) بسبب: {$request->termination_reason}",
            [
                'old_status' => $oldStatus,
                'new_status' => 'TERMINATED',
                'contract_id' => $contract->id,
                'reason' => $request->termination_reason,
                'remaining_obligations' => $request->remaining_obligations ?? 0.00,
            ],
            $user?->id,
            $contract->branch_id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنهاء العقد رسمياً وتسجيل الالتزامات المالية وحالة المقر.',
            'data' => $contract->fresh(),
        ]);
    }

    protected function authorizeContractAccess(BranchContract $contract, $user): void
    {
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
            if ((int)$contract->branch_id !== (int)$user->branch_id) {
                abort(response()->json(['status' => 'error', 'message' => 'غير مصرح بالوصول إلى عقود فرع آخر.'], 403));
            }
        }
    }

    /**
     * Record payment / installment on contract.
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $contract = BranchContract::findOrFail($id);
        $user = Auth::user();

        $this->authorizeContractAccess($contract, $user);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_reference' => 'nullable|string|max:100',
            'installment_id' => 'nullable|exists:contract_installments,id',
            'notes' => 'nullable|string',
        ], [
            'amount.required' => 'مبلغ الدفعة مطلوب.',
        ]);

        $amount = (float)$request->amount;

        DB::transaction(function () use ($contract, $request, $amount, $user) {
            // Update contract paid_value
            $contract->increment('paid_value', $amount);

            if ($request->installment_id) {
                $inst = ContractInstallment::find($request->installment_id);
                if ($inst) {
                    $newPaid = (float)$inst->paid_amount + $amount;
                    $status = $newPaid >= (float)$inst->amount ? 'PAID' : 'PARTIAL';
                    $inst->update([
                        'paid_amount' => $newPaid,
                        'paid_at' => Carbon::now(),
                        'payment_status' => $status,
                        'payment_reference' => $request->payment_reference,
                        'notes' => $request->notes,
                        'recorded_by_id' => $user?->id,
                    ]);
                }
            } else {
                ContractInstallment::create([
                    'contract_id' => $contract->id,
                    'installment_number' => $contract->installments()->count() + 1,
                    'due_date' => Carbon::now()->toDateString(),
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'paid_at' => Carbon::now(),
                    'payment_status' => 'PAID',
                    'payment_reference' => $request->payment_reference,
                    'notes' => $request->notes,
                    'recorded_by_id' => $user?->id,
                ]);
            }

            SystemAuditTrail::log(
                'CONTRACT_PAYMENT_RECORDED',
                "تسجيل دفعة مالية بقيمة {$amount} د.ل للعقد «{$contract->title}» (رقم: {$contract->contract_number})",
                [
                    'amount' => $amount,
                    'contract_id' => $contract->id,
                    'installment_id' => $request->installment_id,
                    'reference' => $request->payment_reference,
                ],
                $user?->id,
                $contract->branch_id
            );
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدفعة وتحديث الرصيد المالي للعقد بنجاح.',
            'data' => $contract->fresh()->load('installments'),
        ]);
    }

    // =========================================================================
    // 3. FINANCIAL ENGINE STATEMENTS (الكشوفات والتقارير المالية)
    // =========================================================================

    public function getPropertyStatement(int $id): JsonResponse
    {
        $property = Property::findOrFail($id);
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id) && $property->branch_id && (int)$property->branch_id !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح باستعراض كشف عقار يتبع فرعاً آخر.'], 403);
        }

        $data = $this->financialEngine->getPropertyFinancialStatement($property);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function getBranchStatement(int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id) && (int)$id !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح باستعراض الكشف المالي لفرع آخر.'], 403);
        }

        $data = $this->financialEngine->getBranchFinancialStatement($id);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function getCentralStatement(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            return response()->json(['status' => 'error', 'message' => 'استعراض التقرير المالي المركزي متاح فقط لمستخدمي الإدارة العامة.'], 403);
        }

        $city = $request->query('city');
        $branchId = $request->query('branch_id');
        $data = $this->financialEngine->getCentralFinancialStatement($city, $branchId ? (int)$branchId : null);

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function getAlerts(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = null;
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id) {
            $branchId = $user->branch_id;
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $branchId = (int)$request->branch_id;
        }

        $alerts = $this->financialEngine->getAutomatedContractAlerts($branchId);

        return response()->json(['status' => 'success', 'data' => $alerts]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function generateInstallmentsSchedule(BranchContract $contract): void
    {
        $freq = $contract->payment_frequency;
        $totalMonths = max(1, $contract->duration_months ?? 12);
        $stepMonths = match ($freq) {
            'QUARTERLY' => 3,
            'SEMI_ANNUAL' => 6,
            'ANNUAL' => 12,
            default => 1,
        };

        $installmentsCount = (int)ceil($totalMonths / $stepMonths);
        $amountPerInstallment = round($contract->total_value / max(1, $installmentsCount), 2);

        $startDate = Carbon::parse($contract->start_date);
        for ($i = 1; $i <= $installmentsCount; $i++) {
            $dueDate = (clone $startDate)->addMonths(($i - 1) * $stepMonths);
            ContractInstallment::create([
                'contract_id' => $contract->id,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'amount' => $amountPerInstallment,
                'paid_amount' => 0.00,
                'payment_status' => 'PENDING',
            ]);
        }
    }
}
