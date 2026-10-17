<?php

namespace Tests\Feature;

use App\Models\AdministrativeDecision;
use App\Models\AdministrativeRequest;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativeApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;
    private User $registrar;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->hq = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $this->registrar = User::where('email', 'registrar.tip@iiis.sch.ly')->firstOrFail();
        $this->branch = Branch::where('city', 'طرابلس')->firstOrFail();
    }

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
            'current_study_year_id' => StudyYear::where('level_order', 1)->firstOrFail()->id,
            'study_type' => 'REGULAR',
            'branch_id' => $this->branch->id,
        ], $overrides);
    }

    private function submitRequest(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->registrar)->postJson('/api/v1/admin-requests', $data);
    }

    private function decide(int $requestId, string $outcome, ?string $notes = 'قرار اختباري')
    {
        return $this->actingAs($this->hq)->postJson("/api/v1/admin-requests/{$requestId}/decide", [
            'outcome' => $outcome,
            'notes' => $notes,
        ]);
    }

    public function test_branch_submits_age_exception_which_stays_pending_until_hq_decides(): void
    {
        $overage = $this->registration(['birth_date' => $this->birthDateForAge(19)]);

        $requestId = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'محمد الورفلي',
            'national_id' => $overage['national_id'],
            'justification' => 'طالب متفوق ومزكى من مجلس الفرع.',
        ])->assertStatus(201)->assertJsonPath('request.status', 'PENDING_HQ')->json('request.id');

        // The registration is refused while the request is pending.
        $this->actingAs($this->registrar)->postJson('/api/v1/students', array_merge($overage, [
            'admission_request_id' => $requestId,
        ]))->assertStatus(422);

        // A branch cannot decide its own request.
        $this->actingAs($this->registrar)->postJson("/api/v1/admin-requests/{$requestId}/decide", [
            'outcome' => 'APPROVED',
        ])->assertStatus(403);
        $this->assertSame('PENDING_HQ', AdministrativeRequest::find($requestId)->status);
    }

    public function test_approved_age_exception_admits_once_and_cannot_be_reused(): void
    {
        $overage = $this->registration(['birth_date' => $this->birthDateForAge(19)]);
        $requestId = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'محمد الورفلي',
            'national_id' => $overage['national_id'],
            'justification' => 'طالب متفوق ومزكى من مجلس الفرع.',
        ])->json('request.id');

        $this->decide($requestId, 'APPROVED')->assertOk()->assertJsonPath('success', true);

        $this->actingAs($this->registrar)->postJson('/api/v1/students', array_merge($overage, [
            'admission_request_id' => $requestId,
        ]))->assertStatus(201);

        $this->assertNotNull(AdministrativeRequest::find($requestId)->consumed_at);

        // A spent approval cannot be consumed again.
        $this->assertFalse(app(\App\Services\AdministrativeRequestService::class)->consume(AdministrativeRequest::find($requestId)));
    }

    public function test_an_approval_does_not_cover_a_different_identity(): void
    {
        $overage = $this->registration(['birth_date' => $this->birthDateForAge(19)]);
        $requestId = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'محمد الورفلي',
            'national_id' => $overage['national_id'],
            'justification' => 'طالب متفوق ومزكى من مجلس الفرع.',
        ])->json('request.id');
        $this->decide($requestId, 'APPROVED')->assertOk();

        $other = $this->registration(['birth_date' => $this->birthDateForAge(19), 'national_id' => '129999999999']);
        $this->actingAs($this->registrar)->postJson('/api/v1/students', array_merge($other, [
            'admission_request_id' => $requestId,
        ]))->assertStatus(422);
    }

    public function test_duplicate_pending_request_for_the_same_identity_is_refused(): void
    {
        $data = [
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'محمد الورفلي',
            'national_id' => '120111111111',
            'justification' => 'طلب أول لهذه الهوية.',
        ];

        $this->submitRequest($data)->assertStatus(201);
        $this->submitRequest($data)->assertStatus(422)->assertJsonValidationErrors(['request_type']);
    }

    public function test_decisions_are_numbered_uniquely_and_a_request_is_decided_once(): void
    {
        $first = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'أول',
            'national_id' => '120222222221',
            'justification' => 'مبرر أول كافٍ.',
        ])->json('request.id');
        $second = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'ثانٍ',
            'national_id' => '120222222222',
            'justification' => 'مبرر ثانٍ كافٍ.',
        ])->json('request.id');

        $d1 = $this->decide($first, 'APPROVED')->assertOk()->json('decision.decision_number');
        $d2 = $this->decide($second, 'REJECTED', 'المبرر غير كافٍ.')->assertOk()->json('decision.decision_number');

        $this->assertNotSame($d1, $d2);
        $this->assertMatchesRegularExpression('/^AD-\d{4}-\d{6}$/', $d1);
        $this->assertSame(2, AdministrativeDecision::count());

        $this->decide($first, 'REJECTED', 'محاولة لإعادة البت.')->assertStatus(422);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $id = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'طالب',
            'national_id' => '120333333333',
            'justification' => 'مبرر كافٍ للطلب.',
        ])->json('request.id');

        $this->decide($id, 'REJECTED', null)->assertStatus(422);
    }

    public function test_second_year_registration_requires_an_approved_grade_request(): void
    {
        $secondYear = StudyYear::where('level_order', 2)->firstOrFail();
        $candidate = $this->registration(['current_study_year_id' => $secondYear->id]);

        // Without approval the enrollment is not created.
        $this->actingAs($this->registrar)->postJson('/api/v1/students', $candidate)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertDatabaseMissing('students', ['national_id' => $candidate['national_id']]);

        $requestId = $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_GRADE_LEVEL,
            'subject_name' => 'محمد الورفلي',
            'national_id' => $candidate['national_id'],
            'study_year_id' => $secondYear->id,
            'justification' => 'انتقال موثّق من معهد آخر بمعادلة معتمدة.',
        ])->json('request.id');

        $this->decide($requestId, 'APPROVED')->assertOk();

        $this->actingAs($this->registrar)->postJson('/api/v1/students', array_merge($candidate, [
            'admission_request_id' => $requestId,
        ]))->assertStatus(201);
        $this->assertDatabaseHas('students', ['national_id' => $candidate['national_id']]);
    }

    public function test_only_hq_can_see_requests_from_every_branch_and_branches_see_their_own(): void
    {
        $this->submitRequest([
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'طالب',
            'national_id' => '120444444444',
            'justification' => 'مبرر كافٍ للطلب.',
        ])->assertStatus(201);

        $this->actingAs($this->registrar)->getJson('/api/v1/admin-requests')
            ->assertOk()
            ->assertJsonCount(1, 'requests');
        $this->actingAs($this->hq)->getJson('/api/v1/admin-requests')
            ->assertOk()
            ->assertJsonCount(1, 'requests');
    }
}
