<?php

namespace App\Rules;

use App\Models\Branch;
use App\Services\AdmissionRulesService;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * يطبّق شروط العمر المركزية على تاريخ الميلاد وفق صفة القيد والجنس وفرع القيد.
 */
class AdmissionAgeRule implements ValidationRule
{
    /**
     * @param bool $exempt true when an approved, unused HQ age-exception request covers this student
     */
    public function __construct(
        private readonly string $studyType,
        private readonly ?string $gender,
        private readonly ?int $branchId,
        private readonly bool $exempt = false,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->exempt) {
            return;
        }

        try {
            $birthDate = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            $fail('تاريخ الميلاد المدخل غير صالح.');

            return;
        }

        $service = app(AdmissionRulesService::class);
        $city = $this->branchId ? Branch::whereKey($this->branchId)->value('city') : null;

        $result = $service->evaluate($this->studyType, $this->gender, $city, $birthDate, $service->referenceDate());

        if (!$result['allowed']) {
            $fail($result['message']);
        }
    }
}
