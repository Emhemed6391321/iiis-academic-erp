<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\Department;
use App\Models\OperationalWindow;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مصفوفة الأدوار: كل دور يصل إلى نطاقه وبحدود صلاحيته فقط، والوحدات المغلقة ترجع 423 لجميع الأدوار.
 */
class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Branch $a;
    private Branch $b;
    private Student $studentA;
    private Student $studentB;
    private BranchContract $contractB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->a = Branch::where('code', '01')->firstOrFail();
        $this->b = Branch::where('code', '02')->firstOrFail();
        $this->studentA = $this->student($this->a, '110000000001');
        $this->studentB = $this->student($this->b, '220000000002');
        $this->contractB = BranchContract::forceCreate([
            'contract_number' => 'CNT-B-1', 'branch_id' => $this->b->id, 'contract_type' => 'إيجار مقر', 'title' => 'عقد ب',
            'contractor_name' => 'مؤجر', 'total_value' => 1000, 'paid_value' => 0, 'start_date' => '2026-01-01',
            'end_date' => '2027-01-01', 'status' => 'active',
        ]);
    }

    private function student(Branch $branch, string $nid): Student
    {
        $year = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::create(['name' => '2026/2027', 'code' => 'AY-MTX', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $department = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SHR-MTX']);

        return Student::create([
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'ع' . $nid,
            'mother_name' => 'أم', 'national_id' => $nid, 'birth_date' => '2005-01-01', 'birth_place' => 'طرابلس',
            'gender' => 'MALE', 'nationality' => 'ليبي', 'nationality_category' => 'LIBYAN',
            'phone' => '091' . substr($nid, 3, 7), 'guardian_phone' => '092' . substr($nid, 3, 7),
            'branch_id' => $branch->id, 'current_study_year_id' => $studyYear->id,
            'enrolled_academic_year_id' => $year->id, 'academic_year_id' => $year->id, 'department_id' => $department->id,
            'academic_status' => 'ENROLLED_ACTIVE', 'study_type' => 'REGULAR', 'is_archived' => false, 'academic_number' => 'ACN-' . $nid,
        ]);
    }

    private function seeded(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function withRole(string $roleName, ?Branch $branch = null): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id, 'branch_id' => $branch?->id, 'must_change_password' => false]);
    }

    private function allUsers(): array
    {
        $users = [];
        foreach (Role::all() as $role) {
            $branch = $role->scope_type === 'BRANCH_SCOPE' ? $this->a : null;
            $users[$role->name] = $this->withRole($role->name, $branch);
        }

        return $users;
    }

    // ------------------------------------------------------------------
    // super_admin
    // ------------------------------------------------------------------

    public function test_super_admin_passes_every_branch_and_every_permission_check(): void
    {
        $admin = $this->seeded('admin@iiis.sch.ly');

        $this->actingAs($admin)->getJson("/api/v1/students/{$this->studentB->id}/file")->assertOk();
        $this->actingAs($admin)->getJson("/api/v1/contracts/{$this->contractB->id}")->assertOk();
        $this->actingAs($admin)->getJson('/api/v1/students')->assertOk();
        $this->assertTrue($admin->hasPermission('anything.at_all'));
    }

    public function test_super_admin_window_override_is_recorded_in_the_audit_trail(): void
    {
        OperationalWindow::where('window_type', 'REGISTRATION')->firstOrFail()
            ->update(['is_active' => true, 'start_at' => Carbon::now()->subDays(30), 'end_at' => Carbon::now()->subDays(2)]);
        $admin = $this->seeded('admin@iiis.sch.ly');

        $response = $this->actingAs($admin)->postJson('/api/v1/students', ['branch_id' => $this->a->id]);
        $this->assertNull($response->json('window_status'), 'The super admin passes a closed window.');

        $log = SystemAuditTrail::where('event_type', 'OPERATIONAL_WINDOW_OVERRIDE')->where('user_id', $admin->id)->first();
        $this->assertNotNull($log, 'The emergency bypass must stay on the record.');
        $this->assertSame('REGISTRATION', $log->payload['window_type'] ?? null);
    }

    // ------------------------------------------------------------------
    // أدوار الإدارة العامة (hq_*)
    // ------------------------------------------------------------------

    public function test_hq_student_affairs_sees_every_branch_but_only_within_its_own_permissions(): void
    {
        $hq = $this->seeded('students.hq@iiis.sch.ly');

        $list = $this->actingAs($hq)->getJson('/api/v1/students')->assertOk();
        $this->assertStringContainsString($this->studentA->national_id, $list->getContent());
        $this->assertStringContainsString($this->studentB->national_id, $list->getContent());
        $this->actingAs($hq)->getJson("/api/v1/students/{$this->studentB->id}/file")->assertOk();

        // No financial, branch-management, user, backup or audit powers.
        $this->actingAs($hq)->deleteJson("/api/v1/contracts/{$this->contractB->id}")->assertForbidden();
        $this->actingAs($hq)->postJson("/api/v1/contracts/{$this->contractB->id}/approve")->assertForbidden();
        $this->actingAs($hq)->putJson("/api/v1/branches/{$this->b->id}", ['name' => 'مخترق'])->assertForbidden();
        $this->actingAs($hq)->postJson('/api/v1/students', ['branch_id' => $this->a->id])->assertForbidden();
        $this->actingAs($hq)->getJson('/api/v1/audit/grade-logs')->assertForbidden();
        $this->assertSame('active', $this->contractB->fresh()->status);

        $this->assertTrue(SystemAuditTrail::where('event_type', 'UNAUTHORIZED_ACCESS_ATTEMPT')->where('user_id', $hq->id)->exists());
    }

    public function test_hq_exams_director_has_exams_powers_but_no_student_affairs_or_finance_powers(): void
    {
        $exams = $this->seeded('exams.hq@iiis.sch.ly');

        $this->actingAs($exams)->getJson("/api/v1/students/{$this->studentB->id}/file")->assertOk();
        $this->actingAs($exams)->postJson("/api/v1/students/{$this->studentB->id}/approve-hq")->assertForbidden();
        $this->actingAs($exams)->postJson("/api/v1/students/{$this->studentB->id}/file/transfer", ['to_branch_id' => $this->a->id])->assertForbidden();
        $this->actingAs($exams)->deleteJson("/api/v1/contracts/{$this->contractB->id}")->assertForbidden();
    }

    public function test_the_student_affairs_clerk_cannot_touch_the_administrative_and_financial_office_domain(): void
    {
        $hq = $this->seeded('students.hq@iiis.sch.ly');

        foreach (['/api/v1/financial/central', '/api/v1/properties'] as $url) {
            $response = $this->actingAs($hq)->getJson($url);
            $this->assertContains($response->getStatusCode(), [200, 403], $url);
        }
        $this->actingAs($hq)->postJson('/api/v1/contracts/' . $this->contractB->id . '/payments', ['amount' => 100])->assertForbidden();
        $this->actingAs($hq)->postJson('/api/v1/properties', ['name' => 'x'])->assertForbidden();
    }

    public function test_hq_roles_without_permissions_are_refused_everywhere(): void
    {
        foreach (['hq_admin_financial', 'hq_legal_office', 'hq_media_office'] as $roleName) {
            $user = $this->withRole($roleName);
            foreach (['/api/v1/students', "/api/v1/students/{$this->studentB->id}/file", '/api/v1/contracts', '/api/v1/attendance/dashboard-stats'] as $url) {
                $this->actingAs($user)->getJson($url)->assertForbidden();
            }
        }
    }

    public function test_every_hq_role_has_global_scope_and_reaches_all_branches_only_with_a_permission(): void
    {
        $hqRoles = Role::all()->filter(fn ($r) => str_starts_with($r->name, 'hq_') || $r->name === 'super_admin');
        $this->assertGreaterThan(10, $hqRoles->count());

        foreach ($hqRoles as $role) {
            $this->assertSame('GLOBAL_SCOPE', $role->scope_type, $role->name);
            $user = $this->withRole($role->name);
            $this->assertTrue($user->isHQ(), $role->name);
        }
    }

    public function test_branches_director_is_global_and_supervises_all_branches(): void
    {
        $director = $this->seeded('branches.director@iiis.sch.ly');

        $this->assertSame('GLOBAL_SCOPE', $director->role->scope_type);
        $this->assertTrue($director->isHQ());
        $this->assertTrue($director->hasGlobalAccessScope());
        $this->assertNull($director->branch_id);
    }

    // ------------------------------------------------------------------
    // أدوار الفروع ومدرّس المادة
    // ------------------------------------------------------------------

    public function test_branch_roles_are_branch_scoped_and_see_their_branch_only(): void
    {
        foreach (['branch_manager', 'branch_registrar', 'branch_exams_officer'] as $roleName) {
            $user = $this->withRole($roleName, $this->a);
            $this->assertFalse($user->isHQ(), $roleName);

            $list = $this->actingAs($user)->getJson('/api/v1/students')->assertOk();
            $this->assertStringContainsString($this->studentA->national_id, $list->getContent(), $roleName);
            $this->assertStringNotContainsString($this->studentB->national_id, $list->getContent(), $roleName);

            $this->assertContains($this->actingAs($user)->getJson("/api/v1/students/{$this->studentB->id}/file")->getStatusCode(), [403, 404], $roleName);
        }
    }

    public function test_a_branch_clerk_cannot_see_another_branch_even_by_filtering(): void
    {
        $registrar = $this->withRole('branch_registrar', $this->a);

        $response = $this->actingAs($registrar)->getJson('/api/v1/students?branch_id=' . $this->b->id);
        $this->assertStringNotContainsString($this->studentB->national_id, $response->getContent());
    }

    public function test_course_instructor_is_confined_and_reaches_no_student_data(): void
    {
        $instructor = $this->withRole('course_instructor', $this->a);
        $this->assertFalse($instructor->isHQ());

        // There is no instructor-to-course assignment in the data model, so the safe behaviour is deny-by-default.
        foreach (["/api/v1/students/{$this->studentA->id}/file", "/api/v1/students/{$this->studentB->id}/file", '/api/v1/students', '/api/v1/grades/sheet'] as $url) {
            $this->assertContains($this->actingAs($instructor)->getJson($url)->getStatusCode(), [403, 404, 423], $url);
        }
        $this->actingAs($instructor)->postJson("/api/v1/students/{$this->studentA->id}/file/notes", ['note' => 'x'])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // الوحدات المغلقة: 423 لجميع الأدوار
    // ------------------------------------------------------------------

    public function test_closed_modules_answer_423_module_closed_to_every_role_including_the_super_admin(): void
    {
        $id = $this->studentA->id;
        $batch = \App\Models\GradeBatch::forceCreate([
            'batch_code' => 'GB-MTX-1', 'branch_id' => $this->a->id, 'academic_year_id' => $this->studentA->enrolled_academic_year_id,
            'study_year_id' => $this->studentA->current_study_year_id, 'department_id' => $this->studentA->department_id,
            'semester' => 1, 'exam_type' => 'REGULAR', 'status' => 'DRAFT',
        ])->id;
        $routes = [
            ['getJson', '/api/v1/study-and-exams/dashboard'],
            ['getJson', '/api/v1/grades/sheet'],
            ['postJson', '/api/v1/grades/batch-save'],
            ['postJson', "/api/v1/grades/batches/{$batch}/submit-hq"],
            ['postJson', "/api/v1/grades/batches/{$batch}/approve-hq"],
            ['getJson', '/api/v1/exams/pending-batches'],
            ['postJson', "/api/v1/exams/batches/{$batch}/reject"],
            ['getJson', "/api/v1/students/{$id}/transcript"],
            ['getJson', "/api/v1/transcripts/students/{$id}"],
        ];

        foreach ($this->allUsers() as $roleName => $user) {
            foreach ($routes as [$method, $url]) {
                $response = $this->actingAs($user)->{$method}($url, []);
                $this->assertSame(423, $response->getStatusCode(), "$roleName -> $url answered " . $response->getStatusCode());
                $response->assertJsonPath('code', 'MODULE_CLOSED');
            }
        }
    }

    public function test_the_module_switch_cannot_open_a_closed_module_even_for_the_super_admin(): void
    {
        $admin = $this->seeded('admin@iiis.sch.ly');

        $this->actingAs($admin)->postJson('/api/v1/modules/states', ['locked' => []])->assertStatus(423);
        $this->assertSame(
            ['study_and_exams', 'grading', 'transcripts', 'batch_print'],
            \App\Http\Controllers\Api\ModuleStateController::lockedSections()
        );
    }
}
