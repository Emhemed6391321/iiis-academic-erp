<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;
use App\Models\AcademicYear;

class MinimumAdmissionAgeRule implements ValidationRule
{
    protected ?Carbon $referenceDate;

    public function __construct(?Carbon $referenceDate = null)
    {
        if ($referenceDate) {
            $this->referenceDate = $referenceDate;
        } else {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $this->referenceDate = $currentYear ? Carbon::parse($currentYear->start_date) : Carbon::now();
        }
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $birthDate = Carbon::parse($value);
            $age = $birthDate->diffInYears($this->referenceDate);

            if ($age < 15) {
                $fail("عفواً، لا يستوفي المتقدم شرط السن الأدنى للقبول (15 عاماً). العمر المحسوب: {$age} سنة.");
            }
        } catch (\Exception $e) {
            $fail("تاريخ الميلاد المدخل غير صالح.");
        }
    }
}
