<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\AdmissionRulesService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionRulesAndIdentityTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->hq = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $this->branch = Branch::where('city', 'طرابلس')->firstOrFail();
    }

    /** Birth date that makes the student exactly $age years old on the current academic year's start. */
    private function birthDateForAge(int $age): string
    {
        $reference = Carbon::parse(AcademicYear::where('is_current', true)->firstOrFail()->start_date);

        return $reference->copy()->subYears($age)->format('Y-m-d');
    }

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'nationality_category' => 'LIBYAN',
            'national_id' => '120' . random_int(100000000, 999999999),
            'first_name' => 'محمد',
            'father_name' => 'علي',
            'grandfather_name' => 'أحمد',
            'family_name' => 'الورفلي',
            'mother_name' => 'فاطمة محمود',
            'gender' => 'MALE',
            'birth_date' => $this->birthDateForAge(16),
            'birth_place' => 'طرابلس',
            'phone' => '091-1112233',
            'guardian_phone' => '091-2223344',
            'department_id' => Department::first()->id,
            'current_study_year_id' => StudyYear::first()->id,
            'study_type' => 'REGULAR',
            'branch_id' => $this->branch->id,
        ], $overrides);
    }

    private function saveRules(array $rules, ?array $cities = null)
    {
        return $this->actingAs($this->hq)->postJson('/api/v1/admin/settings/admission-rules', array_filter([
            'rules' => $rules,
            'cities_with_female_regular_institute' => $cities,
        ], fn ($v) => $v !== null));
    }

    public function test_central_default_regular_band_rejects_students_above_eighteen(): void
    {
        $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration(['birth_date' => $this->birthDateForAge(16)]))
            ->assertStatus(201);

        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'birth_date' => $this->birthDateForAge(19),
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['birth_date']);
        $this->assertStringContainsString('يتجاوز الحد الأعلى', $response->json('errors.birth_date.0'));
    }

    public function test_intisab_male_in_city_without_a_defined_rule_is_refused_not_assumed(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'study_type' => 'INTISAB',
            'gender' => 'MALE',
            'birth_date' => $this->birthDateForAge(25),
        ]));

        $response->assertStatus(422);
        $this->assertStringContainsString('لا توجد قاعدة قبول معتمدة', $response->json('errors.birth_date.0'));
    }

    public function test_intisab_female_in_city_without_female_institute_uses_fifteen_plus(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'study_type' => 'INTISAB',
            'gender' => 'FEMALE',
            'birth_date' => $this->birthDateForAge(15),
        ]));

        $response->assertStatus(201);
    }

    public function test_hq_can_change_age_limits_centrally_and_the_registration_follows(): void
    {
        $this->saveRules([
            ['label' => 'نظامي', 'study_type' => 'REGULAR', 'gender' => null, 'city_scope' => 'ANY', 'min_age' => 15, 'max_age' => 21],
            ['label' => 'انتساب بلا حد', 'study_type' => 'INTISAB', 'gender' => null, 'city_scope' => 'ANY', 'min_age' => 19, 'max_age' => null],
        ])->assertOk()->assertJsonPath('success', true);

        $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'birth_date' => $this->birthDateForAge(20),
        ]))->assertStatus(201);
    }

    public function test_city_list_moves_intisab_students_between_rule_bands(): void
    {
        $this->saveRules(
            [
                ['label' => 'انتساب - مدن بمعهد للبنات', 'study_type' => 'INTISAB', 'gender' => null, 'city_scope' => 'WITH_FEMALE_REGULAR_INSTITUTE', 'min_age' => 19, 'max_age' => null],
                ['label' => 'انتساب للبنات - بدون معهد', 'study_type' => 'INTISAB', 'gender' => 'FEMALE', 'city_scope' => 'WITHOUT_FEMALE_REGULAR_INSTITUTE', 'min_age' => 15, 'max_age' => null],
            ],
            ['طرابلس']
        )->assertOk();

        // In Tripoli, which now has a female regular institute, the 19+ band applies even to females.
        $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'study_type' => 'INTISAB',
            'gender' => 'FEMALE',
            'birth_date' => $this->birthDateForAge(16),
        ]))->assertStatus(422);
    }

    public function test_invalid_rule_sets_are_refused_on_save(): void
    {
        $this->saveRules([
            ['label' => 'عكسي', 'study_type' => 'REGULAR', 'gender' => null, 'city_scope' => 'ANY', 'min_age' => 18, 'max_age' => 15],
        ])->assertStatus(422);

        $this->saveRules([
            ['label' => 'أ', 'study_type' => 'REGULAR', 'gender' => null, 'city_scope' => 'ANY', 'min_age' => 15, 'max_age' => 18],
            ['label' => 'ب', 'study_type' => 'REGULAR', 'gender' => null, 'city_scope' => 'ANY', 'min_age' => 16, 'max_age' => 20],
        ])->assertStatus(422);
    }

    public function test_admission_rules_are_readable_and_guarded_by_settings_permission(): void
    {
        $this->actingAs($this->hq)->getJson('/api/v1/admin/settings/admission-rules')
            ->assertOk()
            ->assertJsonCount(3, 'rules');

        $student = User::where('email', 'registrar.tip@iiis.sch.ly')->firstOrFail();
        $this->actingAs($student)->postJson('/api/v1/admin/settings/admission-rules', ['rules' => AdmissionRulesService::defaultRules()])
            ->assertStatus(403);
    }

    public function test_libyan_student_requires_a_twelve_digit_national_id(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'national_id' => '12345',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['national_id']);
    }

    public function test_libyan_without_national_id_requires_administrative_number(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'nationality_category' => 'LIBYAN_NO_NATIONAL_ID',
            'national_id' => null,
            'administrative_number' => null,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['administrative_number']);

        $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'nationality_category' => 'LIBYAN_NO_NATIONAL_ID',
            'national_id' => null,
            'administrative_number' => 'ADM-2026-0001',
        ]))->assertStatus(201);

        $this->assertDatabaseHas('students', ['administrative_number' => 'ADM-2026-0001', 'national_id' => null]);
    }

    public function test_other_nationality_requires_passport_and_stores_no_national_id(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'nationality_category' => 'OTHER',
            'nationality' => 'مصري',
            'national_id' => null,
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['passport_number']);

        $this->actingAs($this->hq)->postJson('/api/v1/students', $this->registration([
            'nationality_category' => 'OTHER',
            'nationality' => 'مصري',
            'national_id' => null,
            'passport_number' => 'a1234567',
        ]))->assertStatus(201);

        $this->assertDatabaseHas('students', ['passport_number' => 'A1234567', 'nationality_category' => 'OTHER', 'national_id' => null]);
    }

    public function test_duplicate_identity_is_refused_by_validation_and_by_the_database(): void
    {
        $first = $this->registration([
            'nationality_category' => 'OTHER',
            'nationality' => 'مصري',
            'national_id' => null,
            'passport_number' => 'P555111',
        ]);
        $this->actingAs($this->hq)->postJson('/api/v1/students', $first)->assertStatus(201);

        $this->actingAs($this->hq)->postJson('/api/v1/students', array_merge($first, [
            'first_name' => 'آخر',
        ]))->assertStatus(422)->assertJsonValidationErrors(['passport_number']);

        // The unique index is the last line of defence, even if validation were bypassed.
        $this->expectException(QueryException::class);
        Student::query()->insert([
            'academic_number' => 'DUP-1',
            'passport_number' => 'P555111',
            'first_name' => 'x', 'father_name' => 'x', 'grandfather_name' => 'x', 'family_name' => 'x',
            'mother_name' => 'x', 'gender' => 'MALE', 'birth_date' => '2010-01-01', 'birth_place' => 'x',
            'phone' => 'x', 'guardian_phone' => 'x', 'department_id' => Department::first()->id,
            'current_study_year_id' => StudyYear::first()->id, 'study_type' => 'REGULAR',
            'branch_id' => $this->branch->id, 'academic_status' => 'ENROLLED_ACTIVE',
            'nationality_category' => 'OTHER', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
