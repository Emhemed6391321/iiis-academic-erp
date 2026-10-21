<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Branch;
use App\Models\Department;
use App\Models\GradeBatch;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * يتحقق من أن مدير الفرع (branch_manager) يستطيع تنفيذ كل صلاحياته الممنوحة
 * على بيانات فرعه فقط، ولا يستطيع تنفيذ ما هو خارج صلاحياته أو بيانات فرع آخر.
 */
class BranchManagerPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** الصلاحيات الممنوحة لمدير الفرع في AcademicSystemSeeder */
    private const GRANTED = [
        'branches.view',
        'students.view',
        'students.create',
        'students.submit_hq',
        'students.transfer',
        'attendance.view',
        'attendance.record',
        'attendance.reports',
        'grades.view',
        'grades.enter_coursework',
        'grades.enter_final',
        'grades.submit_batch',
        'curriculum.view',
        'reports.print_official',
    ];

    /** صلاحيات مركزية أو خارج نطاق مدير الفرع يجب أن تُرفض */
    private const DENIED = [
        'branches.manage',
        'students.approve_hq',
        'students.delete',
        'grades.approve_hq',
        'curriculum.manage',
        'users.manage',
        'audit.view',
        'windows.manage',
    ];

    private User $manager;
    private Branch $ownBranch;
    private Branch $otherBranch;
    private Student $ownStudent;
    private Student $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $this->ownBranch = Branch::where('code', '01')->firstOrFail();
        $this->otherBranch = Branch::where('code', '02')->firstOrFail();

        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $dept = Department::firstOrFail();
        $studyYear = StudyYear::firstOrFail();

        $this->ownStudent = $this->makeStudent($this->ownBranch, '119900000101', $dept, $studyYear, $year);
        $this->otherStudent = $this->makeStudent($this->otherBranch, '119900000202', $dept, $studyYear, $year);
    }

    private function makeStudent(Branch $branch, string $nationalId, Department $dept, StudyYear $studyYear, AcademicYear $year): Student
    {
        return Student::withoutGlobalScopes()->create([
            'national_id' => $nationalId,
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'first_name' => 'طالب',
            'father_name' => 'أب',
            'grandfather_name' => 'جد',
            'family_name' => 'اختبار',
            'mother_name' => 'أم',
            'gender' => 'MALE',
            'birth_date' => '2005-05-01',
            'birth_place' => 'طرابلس',
            'phone' => '091-1111111',
            'guardian_phone' => '091-2222222',
            'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => $year->id,
        ]);
    }

    public function test_branch_manager_effective_permissions_match_the_granted_set_exactly()
    {
        $this->assertSame('branch_manager', $this->manager->role->name);
        $this->assertSame($this->ownBranch->id, (int) $this->manager->branch_id);

        $effective = $this->manager->role->permissions->pluck('code')->sort()->values()->all();
        $expected = collect(self::GRANTED)->sort()->values()->all();

        $this->assertSame($expected, $effective);
    }

    public function test_branch_manager_is_denied_every_permission_outside_the_branch_role()
    {
        foreach (self::DENIED as $code) {
            $this->assertFalse($this->manager->hasPermission($code), "صلاحية {$code} يجب ألا تكون متاحة لمدير الفرع");
        }
    }

    public function test_branch_manager_can_view_branch_directory_and_own_branch_details()
    {
        $this->actingAs($this->manager)->getJson('/api/v1/branches/directory')->assertOk();
        $this->actingAs($this->manager)->getJson("/api/v1/branches/{$this->ownBranch->id}/details")->assertOk();
    }

    public function test_branch_manager_cannot_view_another_branch_details()
    {
        $this->actingAs($this->manager)
            ->getJson("/api/v1/branches/{$this->otherBranch->id}/details")
            ->assertForbidden();
    }

    public function test_branch_manager_can_view_students_of_own_branch_only()
    {
        $this->actingAs($this->manager)->getJson('/api/v1/students')->assertOk();
        $this->actingAs($this->manager)->getJson("/api/v1/students/{$this->ownStudent->id}")->assertOk();
        $this->actingAs($this->manager)->getJson("/api/v1/students/{$this->otherStudent->id}")->assertNotFound();
    }

    public function test_branch_manager_can_create_a_student_note_for_own_student()
    {
        $this->actingAs($this->manager)
            ->postJson("/api/v1/students/{$this->ownStudent->id}/file/notes", ['note_text' => 'ملاحظة اختبار من مدير الفرع'])
            ->assertSuccessful();
    }

    public function test_branch_manager_can_submit_own_draft_student_to_hq()
    {
        $draft = $this->makeStudent($this->ownBranch, '119900000303', Department::firstOrFail(), StudyYear::firstOrFail(), AcademicYear::where('is_current', true)->firstOrFail());
        $draft->update(['academic_status' => 'NEW_DRAFT']);

        $this->actingAs($this->manager)
            ->postJson("/api/v1/students/{$draft->id}/submit-hq")
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $this->assertSame('PENDING_HQ', $draft->fresh()->academic_status);
    }

    public function test_branch_manager_can_view_and_record_attendance_for_own_branch()
    {
        $this->actingAs($this->manager)->getJson('/api/v1/attendance/dashboard-stats')->assertOk();
        $this->actingAs($this->manager)->getJson('/api/v1/attendance/at-risk-students')->assertOk();

        $this->actingAs($this->manager)
            ->postJson("/api/v1/attendance/single-record/{$this->ownStudent->id}", ['status' => 'PRESENT', 'record_date' => now()->toDateString()])
            ->assertSuccessful();
    }

    /**
     * قسم الدراسة والامتحانات والكنترول مقفل حالياً (قريباً)، لذلك تُحجب واجهات الدرجات
     * بالدرع حتى لصاحب الصلاحية، ولا تصل أي عملية درجات إلى قاعدة البيانات.
     */
    public function test_grades_operations_are_locked_for_branch_manager_while_section_is_coming_soon()
    {
        $this->actingAs($this->manager)->getJson('/api/v1/grades/sheet?' . http_build_query([
            'branch_id' => $this->ownBranch->id,
            'academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
            'department_id' => $this->ownStudent->department_id,
            'study_year_id' => $this->ownStudent->current_study_year_id,
            'semester' => 1,
        ]))->assertStatus(423)->assertJsonPath('status', 'shielded');

        $this->actingAs($this->manager)->postJson('/api/v1/grades/batch-save', [
            'batch_id' => 1,
            'grades' => [['id' => 1, 'coursework' => 1]],
        ])->assertStatus(423);

        $batch = GradeBatch::create([
            'batch_code' => 'BATCH-TEST-LOCK',
            'branch_id' => $this->ownBranch->id,
            'academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
            'study_year_id' => $this->ownStudent->current_study_year_id,
            'department_id' => $this->ownStudent->department_id,
            'semester' => 1,
            'status' => 'DRAFT',
        ]);

        $this->actingAs($this->manager)
            ->postJson("/api/v1/grades/batches/{$batch->id}/submit-hq")
            ->assertStatus(423);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/study-and-exams/dashboard')
            ->assertStatus(423);
    }

    public function test_branch_manager_can_issue_attendance_warning_for_own_student()
    {
        $this->actingAs($this->manager)
            ->postJson("/api/v1/attendance/issue-warning/{$this->ownStudent->id}", [
                'warning_level' => 'FIRST_WARNING',
                'unexcused_days' => 5,
            ])
            ->assertSuccessful();

        $this->actingAs($this->manager)
            ->postJson("/api/v1/attendance/issue-warning/{$this->otherStudent->id}", [
                'warning_level' => 'FIRST_WARNING',
                'unexcused_days' => 5,
            ])
            ->assertNotFound();
    }

    public function test_branch_manager_can_view_curriculum_courses()
    {
        $this->actingAs($this->manager)->getJson('/api/v1/courses')->assertOk();
    }

    public function test_branch_manager_can_print_official_certificate_for_own_student()
    {
        $this->actingAs($this->manager)
            ->getJson("/api/v1/students/{$this->ownStudent->id}/certificates/enrollment")
            ->assertSuccessful();
    }

    public function test_branch_manager_is_forbidden_from_central_actions()
    {
        $this->actingAs($this->manager)->postJson('/api/v1/branches', ['name' => 'فرع جديد', 'code' => '99'])->assertForbidden();
        // مسار الاعتماد المركزي للامتحانات محجوب بالدرع (ShieldedFeatureMiddleware) قبل فحص الصلاحية
        $this->actingAs($this->manager)->getJson('/api/v1/exams/pending-batches')->assertStatus(423);
        $this->actingAs($this->manager)->postJson("/api/v1/students/{$this->ownStudent->id}/approve-hq")->assertForbidden();
        $this->actingAs($this->manager)->getJson('/api/v1/audit/system-trails')->assertForbidden();
        $this->actingAs($this->manager)->postJson('/api/v1/courses', ['name' => 'مقرر'])->assertForbidden();
    }
}
