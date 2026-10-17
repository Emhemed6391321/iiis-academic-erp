<?php

namespace App\Services;

use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * المصدر المركزي لشروط القبول بحسب العمر.
 *
 * القواعد محفوظة في الإعدادات الإدارية المركزية (AdministrativeSetting) ويُطبَّق الحكم نفسه في
 * الواجهة والخادم وواجهات API. عند عدم وجود قاعدة مطابقة لا يُقبل الطالب، ولا يُفترض حد افتراضي.
 */
class AdmissionRulesService
{
    public const RULES_KEY = 'admission_age_rules';
    public const CITIES_KEY = 'cities_with_female_regular_institute';
    public const REFERENCE_DATE_KEY = 'admission_reference_date';

    public const SCOPE_ANY = 'ANY';
    public const SCOPE_WITH_FEMALE_INSTITUTE = 'WITH_FEMALE_REGULAR_INSTITUTE';
    public const SCOPE_WITHOUT_FEMALE_INSTITUTE = 'WITHOUT_FEMALE_REGULAR_INSTITUTE';

    /**
     * الحدود الافتراضية المعتمدة. تُستخدم فقط حتى تحفظ الإدارة العامة قواعدها.
     */
    public static function defaultRules(): array
    {
        return [
            ['label' => 'نظامي - جميع الجنسيات', 'study_type' => 'REGULAR', 'gender' => null, 'city_scope' => self::SCOPE_ANY, 'min_age' => 15, 'max_age' => 18],
            ['label' => 'انتساب - مدن يتوفر فيها معهد نظامي للبنات', 'study_type' => 'INTISAB', 'gender' => null, 'city_scope' => self::SCOPE_WITH_FEMALE_INSTITUTE, 'min_age' => 19, 'max_age' => null],
            ['label' => 'انتساب للبنات - مدن لا يتوفر فيها معهد نظامي للبنات', 'study_type' => 'INTISAB', 'gender' => 'FEMALE', 'city_scope' => self::SCOPE_WITHOUT_FEMALE_INSTITUTE, 'min_age' => 15, 'max_age' => null],
        ];
    }

    /**
     * @return array<int, array{label: string, study_type: string, gender: ?string, city_scope: string, min_age: int, max_age: ?int}>
     */
    public function rules(): array
    {
        $stored = AdminSettingsService::getSetting(self::RULES_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        return is_array($decoded) ? $decoded : self::defaultRules();
    }

    /**
     * @return string[] names of cities where HQ has a regular female institute
     */
    public function citiesWithFemaleInstitute(): array
    {
        $stored = AdminSettingsService::getSetting(self::CITIES_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        return is_array($decoded) ? array_values(array_filter(array_map('trim', $decoded))) : [];
    }

    /**
     * التاريخ المرجعي لحساب العمر: إعداد صريح، وإلا بداية العام الدراسي الحالي (السلوك السابق).
     */
    public function referenceDate(): Carbon
    {
        $explicit = AdminSettingsService::getSetting(self::REFERENCE_DATE_KEY);
        if (is_string($explicit) && $explicit !== '') {
            return Carbon::parse($explicit)->startOfDay();
        }

        $currentYear = AcademicYear::where('is_current', true)->first();

        return $currentYear?->start_date ? Carbon::parse($currentYear->start_date)->startOfDay() : Carbon::now()->startOfDay();
    }

    /**
     * يحكم على الطالب بحسب صفة القيد والجنس والمدينة وتاريخ الميلاد.
     *
     * @return array{allowed: bool, reason: string, message: string, age: int, rule: ?array}
     */
    public function evaluate(string $studyType, ?string $gender, ?string $city, Carbon $birthDate, Carbon $referenceDate): array
    {
        $age = (int) $birthDate->diffInYears($referenceDate, false);
        $inFemaleInstituteCity = $city !== null && in_array(trim($city), $this->citiesWithFemaleInstitute(), true);

        $matches = array_values(array_filter($this->rules(), function (array $rule) use ($studyType, $gender, $inFemaleInstituteCity) {
            if (($rule['study_type'] ?? null) !== $studyType) {
                return false;
            }
            if (($rule['gender'] ?? null) !== null && $rule['gender'] !== $gender) {
                return false;
            }

            return match ($rule['city_scope'] ?? self::SCOPE_ANY) {
                self::SCOPE_WITH_FEMALE_INSTITUTE => $inFemaleInstituteCity,
                self::SCOPE_WITHOUT_FEMALE_INSTITUTE => !$inFemaleInstituteCity,
                default => true,
            };
        }));

        if (empty($matches)) {
            return $this->result(false, 'NO_RULE', 'لا توجد قاعدة قبول معتمدة لهذه الحالة (صفة القيد والجنس والمدينة). يُرجى تحديدها من الإدارة العامة.', $age, null);
        }

        // الأكثر تحديداً أولاً: الجنس المحدد، ثم نطاق المدينة المحدد.
        usort($matches, fn ($a, $b) => [($b['gender'] !== null), ($b['city_scope'] !== self::SCOPE_ANY)] <=> [($a['gender'] !== null), ($a['city_scope'] !== self::SCOPE_ANY)]);
        $rule = $matches[0];

        if ($age < (int) $rule['min_age']) {
            return $this->result(false, 'BELOW_MIN', "عفواً، العمر المحسوب ({$age}) دون الحد الأدنى المعتمد ({$rule['min_age']}) لهذه الحالة.", $age, $rule);
        }

        if ($rule['max_age'] !== null && $age > (int) $rule['max_age']) {
            return $this->result(false, 'ABOVE_MAX', "عفواً، العمر المحسوب ({$age}) يتجاوز الحد الأعلى المعتمد ({$rule['max_age']}) لهذه الحالة. يمكن تقديم طلب استثناء رسمي.", $age, $rule);
        }

        return $this->result(true, 'OK', 'العمر ضمن الحدود المعتمدة.', $age, $rule);
    }

    /**
     * يتحقق من صحة القواعد عند الحفظ ثم يحفظها مركزياً (مع سجل التدقيق عبر AdminSettingsService).
     */
    public function saveRules(array $rules, ?array $citiesWithFemaleInstitute, ?int $userId = null): array
    {
        $validator = Validator::make(['rules' => $rules], [
            'rules' => 'required|array|min:1|max:50',
            'rules.*.label' => 'required|string|max:150',
            'rules.*.study_type' => 'required|in:REGULAR,INTISAB',
            'rules.*.gender' => 'nullable|in:MALE,FEMALE',
            'rules.*.city_scope' => 'required|in:' . implode(',', [self::SCOPE_ANY, self::SCOPE_WITH_FEMALE_INSTITUTE, self::SCOPE_WITHOUT_FEMALE_INSTITUTE]),
            'rules.*.min_age' => 'required|integer|min:0|max:120',
            'rules.*.max_age' => 'nullable|integer|min:0|max:120',
        ]);

        $validator->after(function ($v) use ($rules) {
            $seen = [];
            foreach ($rules as $i => $rule) {
                if (isset($rule['max_age']) && $rule['max_age'] !== '' && (int) $rule['max_age'] < (int) $rule['min_age']) {
                    $v->errors()->add("rules.$i.max_age", 'الحد الأعلى يجب ألا يقل عن الحد الأدنى.');
                }
                $key = ($rule['study_type'] ?? '') . '|' . ($rule['gender'] ?? '*') . '|' . ($rule['city_scope'] ?? '');
                if (isset($seen[$key])) {
                    $v->errors()->add("rules.$i.study_type", 'توجد قاعدة مكررة بالصفة والجنس والنطاق نفسها.');
                }
                $seen[$key] = true;
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $normalized = array_map(fn (array $rule) => [
            'label' => trim($rule['label']),
            'study_type' => $rule['study_type'],
            'gender' => $rule['gender'] ?? null,
            'city_scope' => $rule['city_scope'],
            'min_age' => (int) $rule['min_age'],
            'max_age' => ($rule['max_age'] ?? '') === '' || $rule['max_age'] === null ? null : (int) $rule['max_age'],
        ], $rules);

        AdminSettingsService::setSetting(self::RULES_KEY, $normalized, 'admissions', 'قواعد القبول بحسب العمر', 'json', $userId);

        if ($citiesWithFemaleInstitute !== null) {
            $cities = array_values(array_unique(array_filter(array_map('trim', $citiesWithFemaleInstitute))));
            AdminSettingsService::setSetting(self::CITIES_KEY, $cities, 'admissions', 'المدن التي يتوفر فيها معهد نظامي للبنات', 'json', $userId);
        }

        return $normalized;
    }

    private function result(bool $allowed, string $reason, string $message, int $age, ?array $rule): array
    {
        return compact('allowed', 'reason', 'message', 'age', 'rule');
    }
}
