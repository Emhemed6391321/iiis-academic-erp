<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\BranchFacility;
use App\Models\BranchRequest;
use App\Models\Department;
use App\Models\ExcuseRequest;
use App\Models\InventoryItem;
use App\Models\OperationalWindow;
use App\Models\OperationalWindowException;
use App\Models\Property;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\StudyYear;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مدير الفرع (branch_manager) لا يقرأ ولا يعدّل ولا يحذف أي سجل يتبع فرعاً آخر، حتى مع معرفة المعرّف مباشرة (IDOR)،
 * ولا يستطيع تغيير branch_id في طلباته، وتُسجَّل محاولاته المخالفة في سجل التدقيق.
 */
class BranchManagerIdorIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Branch $own;
    private Branch $other;
    private Branch $third;
    private Student $ownStudent;
    private Student $foreignStudent;
    private ExcuseRequest $foreignExcuse;
    private BranchContract $foreignContract;
    private Property $foreignProperty;
    private BranchFacility $foreignFacility;
    private BranchRequest $foreignTicket;
    private StudentTransfer $foreignTransfer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $this->own = Branch::where('code', '01')->firstOrFail();
        $this->other = Branch::where('code', '02')->firstOrFail();
        $this->third = Branch::where('code', '03')->firstOrFail();

        $this->ownStudent = $this->student($this->own, '100000000001');
        $this->foreignStudent = $this->student($this->other, '200000000002');

        $this->foreignExcuse = ExcuseRequest::forceCreate([
            'student_id' => $this->foreignStudent->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-02',
            'reason' => 'عذر سري لفرع آخر', 'status' => 'PENDING',
        ]);

        $this->foreignContract = BranchContract::forceCreate([
            'contract_number' => 'CNT-OTHER-1', 'branch_id' => $this->other->id, 'contract_type' => 'إيجار مقر',
            'title' => 'عقد فرع آخر', 'contractor_name' => 'مؤجر آخر', 'total_value' => 1000, 'paid_value' => 0,
            'start_date' => '2026-01-01', 'end_date' => '2027-01-01', 'status' => 'active',
        ]);

        $this->foreignProperty = Property::forceCreate([
            'property_number' => 'PRP-OTHER-1', 'name' => 'مقر فرع آخر', 'type' => 'BUILDING', 'branch_id' => $this->other->id,
        ]);

        $this->foreignFacility = BranchFacility::forceCreate([
            'branch_id' => $this->other->id, 'facility_type' => 'قاعات دراسية', 'count' => 4,
        ]);

        $this->foreignTicket = BranchRequest::forceCreate([
            'ticket_number' => 'TK-OTHER-1', 'branch_id' => $this->other->id, 'category' => 'MAINTENANCE',
            'priority' => 'medium', 'title' => 'بلاغ فرع آخر', 'description' => 'وصف', 'status' => 'pending',
        ]);

        // A transfer between two branches that are both foreign to the manager.
        $this->foreignTransfer = StudentTransfer::forceCreate([
            'student_id' => $this->foreignStudent->id, 'from_branch_id' => $this->other->id, 'to_branch_id' => $this->third->id,
            'status' => 'PENDING', 'reason' => 'نقل بين فرعين آخرين', 'requested_by' => User::where('email', 'admin@iiis.sch.ly')->value('id'),
        ]);

        InventoryItem::forceCreate(['item_code' => 'INV-1', 'name' => 'مادة مخزنية', 'category' => 'قرطاسية']);
    }

    private function student(Branch $branch, string $nationalId): Student
    {
        $year = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::create(['name' => '2026/2027', 'code' => 'AY-IDOR', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $department = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SHR-IDOR']);

        return Student::create([
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'ع' . $nationalId,
            'mother_name' => 'أم', 'national_id' => $nationalId, 'birth_date' => '2005-01-01', 'birth_place' => 'طرابلس',
            'gender' => 'MALE', 'nationality' => 'ليبي', 'nationality_category' => 'LIBYAN',
            'phone' => '091' . substr($nationalId, 3, 7), 'guardian_phone' => '092' . substr($nationalId, 3, 7),
            'branch_id' => $branch->id, 'current_study_year_id' => $studyYear->id,
            'enrolled_academic_year_id' => $year->id, 'academic_year_id' => $year->id, 'department_id' => $department->id,
            'academic_status' => 'ENROLLED_ACTIVE', 'study_type' => 'REGULAR', 'is_archived' => false,
            'academic_number' => 'ACN-' . $nationalId,
        ]);
    }

    private function assertDenied($response): void
    {
        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'A record of another branch must be refused, got ' . $response->getStatusCode() . ': ' . substr($response->getContent(), 0, 300)
        );
    }

    // ------------------------------------------------------------------
    // قراءة سجلات فرع آخر عبر المعرّف المباشر
    // ------------------------------------------------------------------

    public function test_manager_cannot_read_a_foreign_student_through_any_student_endpoint(): void
    {
        $id = $this->foreignStudent->id;

        foreach ([
            "/api/v1/students/{$id}",
            "/api/v1/students/{$id}/file",
            "/api/v1/students/{$id}/file/notes",
            "/api/v1/students/{$id}/file/excuses",
            "/api/v1/students/{$id}/file/behaviors",
            "/api/v1/students/{$id}/file/attendance",
            "/api/v1/students/{$id}/file/timeline",
            "/api/v1/students/{$id}/card",
            "/api/v1/students/{$id}/certificates/enrollment",
            "/api/v1/students/{$id}/certificates/conduct",
            "/api/v1/students/{$id}/confidential-report",
            "/api/v1/attendance/student/{$id}",
        ] as $url) {
            $response = $this->actingAs($this->manager)->getJson($url);
            $this->assertDenied($response);
            $this->assertStringNotContainsString($this->foreignStudent->national_id, $response->getContent(), $url);
        }
    }

    public function test_manager_still_reads_the_own_student(): void
    {
        $this->actingAs($this->manager)->getJson("/api/v1/students/{$this->ownStudent->id}/file")->assertOk();
    }

    public function test_lists_never_contain_records_of_another_branch(): void
    {
        $foreignNid = $this->foreignStudent->national_id;

        foreach (['/api/v1/students', '/api/v1/students/registry', '/api/v1/students/registry/official', '/api/v1/excuses'] as $url) {
            $response = $this->actingAs($this->manager)->getJson($url);
            $response->assertOk();
            $this->assertStringNotContainsString($foreignNid, $response->getContent(), $url);
            $this->assertStringNotContainsString('عذر سري لفرع آخر', $response->getContent(), $url);
        }

        foreach (['/api/v1/contracts', '/api/v1/branches/contracts', '/api/v1/properties', '/api/v1/branches/requests'] as $url) {
            $response = $this->actingAs($this->manager)->getJson($url);
            $response->assertOk();
            foreach (['CNT-OTHER-1', 'PRP-OTHER-1', 'TK-OTHER-1', 'عقد فرع آخر', 'مقر فرع آخر', 'بلاغ فرع آخر'] as $secret) {
                $this->assertStringNotContainsString($secret, $response->getContent(), "$url leaks $secret");
            }
        }
    }

    public function test_manager_cannot_read_contracts_properties_facilities_or_finance_of_another_branch(): void
    {
        $this->assertDenied($this->actingAs($this->manager)->getJson("/api/v1/contracts/{$this->foreignContract->id}"));
        $this->assertDenied($this->actingAs($this->manager)->getJson("/api/v1/properties/{$this->foreignProperty->id}"));
        $this->assertDenied($this->actingAs($this->manager)->getJson("/api/v1/financial/property/{$this->foreignProperty->id}"));
        $this->assertDenied($this->actingAs($this->manager)->getJson("/api/v1/financial/branch/{$this->other->id}"));
        $this->assertDenied($this->actingAs($this->manager)->getJson("/api/v1/branches/{$this->other->id}/details"));

        $overview = $this->actingAs($this->manager)->getJson('/api/v1/branches/overview')->assertOk();
        $this->assertStringNotContainsString('CNT-OTHER-1', $overview->getContent());
    }

    // ------------------------------------------------------------------
    // تعديل وحذف سجلات فرع آخر
    // ------------------------------------------------------------------

    public function test_manager_cannot_modify_archive_delete_or_submit_a_foreign_student(): void
    {
        $id = $this->foreignStudent->id;
        $before = $this->foreignStudent->fresh()->only(['first_name', 'branch_id', 'phone', 'is_archived', 'academic_status']);

        foreach ([
            ['putJson', "/api/v1/students/{$id}", ['first_name' => 'مخترق', 'branch_id' => $this->own->id]],
            ['postJson', "/api/v1/students/{$id}", ['first_name' => 'مخترق', 'branch_id' => $this->own->id]],
            ['deleteJson', "/api/v1/students/{$id}", []],
            ['postJson', "/api/v1/students/{$id}/archive", []],
            ['postJson', "/api/v1/students/{$id}/restore", []],
            ['postJson', "/api/v1/students/{$id}/submit-hq", []],
            ['postJson', "/api/v1/students/{$id}/file/notes", ['note' => 'ملاحظة دخيلة', 'content' => 'ملاحظة دخيلة']],
            ['postJson', "/api/v1/students/{$id}/file/behaviors", ['violation_type' => 'x', 'warning_level' => 'LEVEL_1']],
            ['postJson', "/api/v1/students/{$id}/file/excuses", ['start_date' => '2026-10-01', 'end_date' => '2026-10-02', 'reason' => 'دخيل']],
            ['postJson', "/api/v1/students/{$id}/file/transfer", ['to_branch_id' => $this->own->id, 'reason' => 'سحب طالب من فرع آخر']],
            ['postJson', "/api/v1/students/{$id}/file/change-status", ['status' => 'SUSPENDED']],
        ] as [$method, $url, $body]) {
            $this->assertDenied($this->actingAs($this->manager)->{$method}($url, $body));
        }

        $this->assertSame($before, $this->foreignStudent->fresh()->only(['first_name', 'branch_id', 'phone', 'is_archived', 'academic_status']));
        $this->assertDatabaseMissing('student_notes', ['student_id' => $id]);
        $this->assertDatabaseMissing('student_behaviors', ['student_id' => $id]);
        $this->assertSame(1, StudentTransfer::where('student_id', $id)->count(), 'No transfer may be opened for a foreign student.');
    }

    public function test_manager_cannot_review_a_foreign_excuse_or_step_a_foreign_transfer(): void
    {
        $this->assertDenied($this->actingAs($this->manager)->postJson("/api/v1/excuses/{$this->foreignExcuse->id}/review", ['status' => 'APPROVED']));
        $this->assertSame('PENDING', $this->foreignExcuse->fresh()->status);

        foreach (['CENTRAL_MEMO', 'RECEIVING_BRANCH', 'HQ_FINAL'] as $step) {
            $this->assertDenied($this->actingAs($this->manager)->postJson(
                "/api/v1/student-workflow/transfer-request/{$this->foreignTransfer->id}/step",
                ['step' => $step, 'action' => 'APPROVE', 'status' => 'APPROVED']
            ));
        }
        $this->assertSame('PENDING', $this->foreignTransfer->fresh()->status);
        $this->assertSame($this->other->id, $this->foreignStudent->fresh()->branch_id);
    }

    public function test_manager_cannot_change_or_delete_contracts_properties_and_tickets_of_another_branch(): void
    {
        $id = $this->foreignContract->id;
        foreach ([
            ['postJson', "/api/v1/contracts/{$id}/approve", []],
            ['postJson', "/api/v1/contracts/{$id}/terminate", []],
            ['postJson', "/api/v1/contracts/{$id}/payments", ['amount' => 500]],
            ['deleteJson', "/api/v1/contracts/{$id}", []],
            ['putJson', "/api/v1/properties/{$this->foreignProperty->id}", ['name' => 'مخترق']],
            ['deleteJson', "/api/v1/properties/{$this->foreignProperty->id}", []],
            ['putJson', "/api/v1/branches/{$this->other->id}", ['name' => 'مخترق']],
            ['postJson', "/api/v1/branches/{$this->other->id}/suspend", []],
            ['deleteJson', "/api/v1/branches/{$this->other->id}", []],
            ['patchJson', "/api/v1/branches/requests/{$this->foreignTicket->id}/status", ['status' => 'completed']],
        ] as [$method, $url, $body]) {
            $this->assertDenied($this->actingAs($this->manager)->{$method}($url, $body));
        }

        $this->assertSame('active', $this->foreignContract->fresh()->status);
        $this->assertSame('مقر فرع آخر', $this->foreignProperty->fresh()->name);
        $this->assertSame('pending', $this->foreignTicket->fresh()->status);
        $this->assertNotSame('مخترق', $this->other->fresh()->name);
        $this->assertSame(4, $this->foreignFacility->fresh()->count);
    }

    // ------------------------------------------------------------------
    // العبث بـ branch_id
    // ------------------------------------------------------------------

    public function test_manager_cannot_move_the_own_student_to_another_branch_by_sending_branch_id(): void
    {
        $this->actingAs($this->manager)->putJson("/api/v1/students/{$this->ownStudent->id}", [
            'first_name' => $this->ownStudent->first_name,
            'branch_id' => $this->other->id,
        ]);

        $this->assertSame($this->own->id, $this->ownStudent->fresh()->branch_id);
    }

    public function test_manager_cannot_open_a_ticket_or_a_student_on_behalf_of_another_branch(): void
    {
        $this->actingAs($this->manager)->postJson('/api/v1/branches/requests', [
            'branch_id' => $this->other->id, 'category' => 'MAINTENANCE', 'priority' => 'low',
            'title' => 'بلاغ بنية فرع آخر', 'description' => 'وصف البلاغ',
        ]);

        $this->assertDatabaseMissing('branch_requests', ['title' => 'بلاغ بنية فرع آخر', 'branch_id' => $this->other->id]);
    }

    public function test_manager_cannot_filter_lists_down_to_another_branch_with_branch_id(): void
    {
        foreach (['/api/v1/students?branch_id=' . $this->other->id, '/api/v1/students/registry?branch_id=' . $this->other->id] as $url) {
            $response = $this->actingAs($this->manager)->getJson($url);
            $this->assertStringNotContainsString($this->foreignStudent->national_id, $response->getContent(), $url);
        }
    }

    // ------------------------------------------------------------------
    // النافذة التشغيلية والتدقيق
    // ------------------------------------------------------------------

    private function closeRegistration(): OperationalWindow
    {
        $window = OperationalWindow::where('window_type', 'REGISTRATION')->firstOrFail();
        $window->update(['is_active' => true, 'start_at' => Carbon::now()->subDays(30), 'end_at' => Carbon::now()->subDays(2)]);

        return $window;
    }

    public function test_closed_window_blocks_the_manager_and_the_attempt_is_audited(): void
    {
        $this->closeRegistration();

        $response = $this->actingAs($this->manager)->postJson('/api/v1/students', ['branch_id' => $this->own->id]);

        $response->assertStatus(403)->assertJsonPath('window_status', 'CLOSED');

        $log = SystemAuditTrail::where('event_type', 'UNAUTHORIZED_ACCESS_ATTEMPT')->where('user_id', $this->manager->id)->latest('id')->first();
        $this->assertNotNull($log, 'The refused attempt must be recorded in the audit trail.');
        $this->assertSame('REGISTRATION', $log->payload['window_type'] ?? null);
        $this->assertSame('CLOSED', $log->payload['window_status'] ?? null);
        $this->assertSame($this->own->id, $log->branch_id);
    }

    public function test_an_approved_extension_reopens_only_the_extended_branch_and_its_expiry_closes_it_again(): void
    {
        $window = $this->closeRegistration();
        $granter = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $exception = OperationalWindowException::create([
            'window_id' => $window->id, 'branch_id' => $this->own->id, 'granted_by' => $granter->id,
            'extended_until' => Carbon::now()->addDays(3), 'reason' => 'تمديد معتمد',
        ]);

        $open = $this->actingAs($this->manager)->postJson('/api/v1/students', ['branch_id' => $this->own->id]);
        $this->assertNull($open->json('window_status'), 'The extended branch passes the window.');

        // The other branch's manager gets no benefit from this branch's extension.
        $otherManager = User::factory()->create(['role_id' => $this->manager->role_id, 'branch_id' => $this->other->id, 'must_change_password' => false]);
        $this->actingAs($otherManager)->postJson('/api/v1/students', ['branch_id' => $this->other->id])
            ->assertStatus(403)->assertJsonPath('window_status', 'CLOSED');

        $exception->update(['extended_until' => Carbon::now()->subMinute()]);
        $this->actingAs($this->manager)->postJson('/api/v1/students', ['branch_id' => $this->own->id])
            ->assertStatus(403)->assertJsonPath('window_status', 'CLOSED');
    }

    public function test_every_window_refusal_is_audited_not_only_the_closed_one(): void
    {
        OperationalWindow::where('window_type', 'REGISTRATION')->firstOrFail()->update(['is_active' => false]);
        $this->actingAs($this->manager)->postJson('/api/v1/students', [])->assertJsonPath('window_status', 'DISABLED');

        OperationalWindow::where('window_type', 'REGISTRATION')->delete();
        $this->actingAs($this->manager)->postJson('/api/v1/students', [])->assertJsonPath('window_status', 'NOT_CONFIGURED');

        $statuses = SystemAuditTrail::where('event_type', 'UNAUTHORIZED_ACCESS_ATTEMPT')
            ->where('user_id', $this->manager->id)->get()->pluck('payload.window_status')->all();
        $this->assertContains('DISABLED', $statuses);
        $this->assertContains('NOT_CONFIGURED', $statuses);
    }

    public function test_a_refused_permission_is_audited_as_unauthorized_access(): void
    {
        $this->actingAs($this->manager)->deleteJson("/api/v1/contracts/{$this->foreignContract->id}")->assertForbidden();

        $log = SystemAuditTrail::where('event_type', 'UNAUTHORIZED_ACCESS_ATTEMPT')->where('user_id', $this->manager->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('branches.manage', $log->payload['required_permission'] ?? null);
        $this->assertSame('BRANCH_SCOPE', $log->payload['user_scope'] ?? null);
    }

    public function test_manager_cannot_grant_himself_a_window_exception_or_open_windows(): void
    {
        $window = OperationalWindow::where('window_type', 'REGISTRATION')->firstOrFail();

        $response = $this->actingAs($this->manager)->postJson('/api/v1/settings/operational-windows/exception', [
            'window_id' => $window->id, 'branch_id' => $this->own->id, 'extended_until' => Carbon::now()->addDays(30)->toDateTimeString(), 'reason' => 'تمديد ذاتي',
        ]);
        $this->assertContains($response->getStatusCode(), [403, 404, 405]);
        $this->assertSame(0, OperationalWindowException::count());
    }
}
