<?php

namespace App\Services;

use App\Models\AdministrativeDecision;
use App\Models\AdministrativeRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مسار الاعتماد الرسمي: يقدّم الفرع الطلب، ولا تعتمده إلا الإدارة العامة.
 * الطلب المعتمد يُستهلك مرة واحدة فقط عند تنفيذه، والقرار يصدر برقم فريد لا يُعاد استخدامه.
 */
class AdministrativeRequestService
{
    public const OUTCOME_APPROVED = 'APPROVED';
    public const OUTCOME_REJECTED = 'REJECTED';

    /** Job position code of the institute director (المدير العام للمعهد) in the central settings. */
    public const DIRECTOR_POSITION_CODE = 'DIR_GEN';

    /**
     * من يحق له البت في الطلب. استثناء شرط العمر لا يتجاوزه إلا مدير المعهد (المدير العام) وحده،
     * ولا تكفيه صلاحية الاعتماد العامة. باقي أنواع الطلبات تكفيها صلاحية الإدارة العامة.
     */
    public static function canDecide(AdministrativeRequest $request, User $user): bool
    {
        if ($request->request_type === AdministrativeRequest::TYPE_AGE_EXCEPTION) {
            return $user->isSuperAdmin() || $user->currentPlacement?->jobPosition?->code === self::DIRECTOR_POSITION_CODE;
        }

        return $user->hasGlobalAccessScope();
    }

    /**
     * يوحّد صيغة الهوية بالطريقة نفسها التي تُحفظ بها في الطالب.
     */
    public static function normalizeIdentity(array $input): array
    {
        $national = preg_replace('/\s+/', '', (string) ($input['national_id'] ?? ''));
        $passport = strtoupper(trim((string) ($input['passport_number'] ?? '')));
        $administrative = trim((string) ($input['administrative_number'] ?? ''));

        return [
            'national_id' => $national !== '' ? $national : null,
            'passport_number' => $passport !== '' ? $passport : null,
            'administrative_number' => $administrative !== '' ? $administrative : null,
        ];
    }

    public function submit(array $data, int $branchId, User $requester): AdministrativeRequest
    {
        $identity = self::normalizeIdentity($data);

        if (empty(array_filter($identity))) {
            throw ValidationException::withMessages([
                'identity' => 'يجب تحديد هوية الطالب (الرقم الوطني أو الرقم الإداري أو رقم الجواز).',
            ]);
        }

        return DB::transaction(function () use ($data, $branchId, $requester, $identity) {
            $duplicatePending = AdministrativeRequest::query()
                ->where('request_type', $data['request_type'])
                ->where('status', AdministrativeRequest::STATUS_PENDING)
                ->when(!empty($data['study_year_id']), fn (Builder $q) => $q->where('study_year_id', $data['study_year_id']))
                ->where(fn (Builder $q) => $this->matchIdentity($q, $identity))
                ->exists();

            if ($duplicatePending) {
                throw ValidationException::withMessages([
                    'request_type' => 'يوجد طلب معلّق بالنوع نفسه لهذه الهوية، ولا يُقدَّم طلب مكرر.',
                ]);
            }

            return AdministrativeRequest::create([
                'request_type' => $data['request_type'],
                'status' => AdministrativeRequest::STATUS_PENDING,
                'branch_id' => $branchId,
                'requested_by' => $requester->id,
                'subject_name' => $data['subject_name'],
                'national_id' => $identity['national_id'],
                'passport_number' => $identity['passport_number'],
                'administrative_number' => $identity['administrative_number'],
                'study_year_id' => $data['study_year_id'] ?? null,
                'justification' => $data['justification'],
            ]);
        });
    }

    /**
     * يصدر قرار الاعتماد أو الرفض. الطلب يُقفل أثناء البت لمنع اعتماده مرتين.
     */
    public function decide(AdministrativeRequest $request, User $decider, string $outcome, ?string $notes): AdministrativeDecision
    {
        return DB::transaction(function () use ($request, $decider, $outcome, $notes) {
            $locked = AdministrativeRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== AdministrativeRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'تم البت في هذا الطلب مسبقاً، ولا يُعاد اعتماده.']);
            }

            $now = Carbon::now();

            $decision = AdministrativeDecision::create([
                'decision_number' => $this->nextDecisionNumber($now),
                'request_id' => $locked->id,
                'outcome' => $outcome,
                'issued_by' => $decider->id,
                'issued_at' => $now,
                'notes' => $notes,
            ]);

            $locked->update([
                'status' => $outcome === self::OUTCOME_APPROVED
                    ? AdministrativeRequest::STATUS_APPROVED
                    : AdministrativeRequest::STATUS_REJECTED,
                'decided_by' => $decider->id,
                'decided_at' => $now,
                'decision_notes' => $notes,
            ]);

            return $decision;
        });
    }

    /**
     * أقرب طلب معتمد وغير مستهلك يطابق الهوية (ومرحلة الدراسة عند الحاجة).
     */
    public function findApproved(string $type, array $identity, ?int $studyYearId = null): ?AdministrativeRequest
    {
        if (empty(array_filter($identity))) {
            return null;
        }

        return AdministrativeRequest::query()
            ->where('request_type', $type)
            ->where('status', AdministrativeRequest::STATUS_APPROVED)
            ->whereNull('consumed_at')
            ->when($studyYearId !== null, fn (Builder $q) => $q->where('study_year_id', $studyYearId))
            ->where(fn (Builder $q) => $this->matchIdentity($q, $identity))
            ->orderByDesc('decided_at')
            ->first();
    }

    /**
     * يستهلك الطلب المعتمد مرة واحدة. يعيد false إذا استُهلك في الأثناء.
     */
    public function consume(AdministrativeRequest $request): bool
    {
        return AdministrativeRequest::whereKey($request->id)
            ->where('status', AdministrativeRequest::STATUS_APPROVED)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]) === 1;
    }

    /**
     * رقم قرار تسلسلي لكل عام: AD-YYYY-000001. الرقم الفريد في قاعدة البيانات يمنع أي تكرار.
     */
    private function nextDecisionNumber(Carbon $now): string
    {
        $prefix = 'AD-' . $now->format('Y') . '-';
        $last = AdministrativeDecision::query()
            ->where('decision_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max('decision_number');

        $sequence = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function matchIdentity(Builder $query, array $identity): void
    {
        foreach (array_filter($identity) as $field => $value) {
            $query->orWhere($field, $value);
        }
    }
}
