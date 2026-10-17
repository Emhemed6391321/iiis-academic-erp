<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRequest;
use App\Models\SystemAuditTrail;
use App\Services\AdministrativeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdministrativeRequestController extends Controller
{
    public function __construct(private readonly AdministrativeRequestService $requests)
    {
    }

    /**
     * قائمة الطلبات وحالتها. الفرع يرى طلباته فقط، والإدارة العامة ترى الكل.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = AdministrativeRequest::query()
            ->with(['branch:id,name', 'requester:id,name', 'decider:id,name', 'decision:id,request_id,decision_number,outcome,issued_at'])
            ->latest('id');

        if (!$user->hasGlobalAccessScope()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->string('request_type'));
        }

        // can_decide tells the screen which decision buttons to show; the decide endpoint enforces it again.
        $requests = $query->limit(200)->get()->map(function (AdministrativeRequest $item) use ($user) {
            $item->setAttribute(
                'can_decide',
                $item->status === AdministrativeRequest::STATUS_PENDING && AdministrativeRequestService::canDecide($item, $user)
            );

            return $item;
        });

        return response()->json([
            'success' => true,
            'requests' => $requests,
        ]);
    }

    /**
     * يقدّم الفرع طلب استثناء أو تسجيل. لا يُنشأ أي قيد عن الطلب قبل اعتماده.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'request_type' => 'required|in:' . implode(',', [AdministrativeRequest::TYPE_AGE_EXCEPTION, AdministrativeRequest::TYPE_GRADE_LEVEL]),
            'subject_name' => 'required|string|max:150',
            'national_id' => 'nullable|string|max:20',
            'passport_number' => 'nullable|string|max:50',
            'administrative_number' => 'nullable|string|max:50',
            'study_year_id' => 'required_if:request_type,' . AdministrativeRequest::TYPE_GRADE_LEVEL . '|nullable|exists:study_years,id',
            'branch_id' => 'nullable|exists:branches,id',
            'justification' => 'required|string|min:10|max:2000',
        ]);

        $branchId = $user->hasGlobalAccessScope() && !empty($validated['branch_id'])
            ? (int) $validated['branch_id']
            : (int) $user->branch_id;

        if (!$branchId) {
            return response()->json(['success' => false, 'message' => 'تعذّر تحديد الفرع الذي يُقدَّم عنه الطلب.'], 422);
        }

        $created = $this->requests->submit($validated, $branchId, $user);

        SystemAuditTrail::log(
            'ADMIN_REQUEST_SUBMITTED',
            "تقديم طلب اعتماد ({$created->request_type}) للطالب: {$created->subject_name}",
            ['request_id' => $created->id],
            $user->id,
            $branchId,
            AdministrativeRequest::class,
            $created->id,
            [],
            ['status' => $created->status],
            'INFO',
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم الطلب وإحالته إلى الإدارة العامة للاعتماد. لا يُنفَّذ أي إجراء قبل صدور القرار.',
            'request' => $created,
        ], 201);
    }

    /**
     * قرار الإدارة العامة: اعتماد أو رفض. الفرع لا يعتمد طلباته بنفسه.
     */
    public function decide(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasGlobalAccessScope()) {
            return response()->json(['success' => false, 'message' => 'قرار الاعتماد من صلاحيات الإدارة العامة فقط.'], 403);
        }

        $validated = $request->validate([
            'outcome' => 'required|in:' . AdministrativeRequestService::OUTCOME_APPROVED . ',' . AdministrativeRequestService::OUTCOME_REJECTED,
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validated['outcome'] === AdministrativeRequestService::OUTCOME_REJECTED && empty($validated['notes'])) {
            return response()->json(['success' => false, 'message' => 'يجب ذكر سبب الرفض.'], 422);
        }

        $target = AdministrativeRequest::findOrFail($id);

        if (!AdministrativeRequestService::canDecide($target, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'استثناء شرط العمر لا يتجاوزه إلا مدير المعهد، ولا يكفي اعتماد الإدارة العامة.',
            ], 403);
        }

        $decision = $this->requests->decide($target, $user, $validated['outcome'], $validated['notes'] ?? null);

        SystemAuditTrail::log(
            'ADMIN_REQUEST_DECIDED',
            "قرار {$decision->outcome} رقم {$decision->decision_number} على الطلب #{$target->id}",
            ['request_id' => $target->id, 'decision_number' => $decision->decision_number],
            $user->id,
            $target->branch_id,
            AdministrativeRequest::class,
            $target->id,
            ['status' => AdministrativeRequest::STATUS_PENDING],
            ['status' => $decision->outcome],
            'INFO',
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "تم إصدار القرار الإداري رقم {$decision->decision_number}.",
            'decision' => $decision,
        ]);
    }
}
