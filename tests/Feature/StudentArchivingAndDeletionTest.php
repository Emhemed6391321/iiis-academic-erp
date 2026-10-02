<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentArchivingAndDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branch1Manager;
    protected User $branch2Manager;
    protected Branch $branch1;
    protected Branch $branch2;
    protected AcademicYear $academicYear;
    protected StudyYear $studyYear;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']
        );

        $branchManagerRole = Role::firstOrCreate(
            ['name' => 'branch_manager'],
            ['display_name' => 'مدير فرع', 'scope_type' => 'BRANCH_SCOPE']
        );

        $this->branch1 = Branch::first() ?? Branch::create([
            'name' => 'فرع طرابلس المركزي',
            'code' => 'TIP-01',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::where('id', '!=', $this->branch1->id)->first() ?? Branch::create([
            'name' => 'فرع بنغازي',
            'code' => 'BEN-02',
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::create([
            'name' => '2025/2026',
            'code' => 'AY-2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $this->studyYear = StudyYear::first() ?? StudyYear::create([
            'name' => 'السنة الأولى',
            'order' => 1,
        ]);

        $this->department = Department::first() ?? Department::create([
            'name' => 'الشريعة والقانون',
            'code' => 'SHR-01',
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'المدير العام للمعهد',
            'email' => 'superadmin@institute.ly',
            'role_id' => $superAdminRole->id,
            'branch_id' => null,
            'is_active' => true,
        ]);

        $this->branch1Manager = User::factory()->create([
            'name' => 'مدير فرع طرابلس',
            'email' => 'manager1@institute.ly',
            'role_id' => $branchManagerRole->id,
            'branch_id' => $this->branch1->id,
            'is_active' => true,
        ]);

        $this->branch2Manager = User::factory()->create([
            'name' => 'مدير فرع بنغازي',
            'email' => 'manager2@institute.ly',
            'role_id' => $branchManagerRole->id,
            'branch_id' => $this->branch2->id,
            'is_active' => true,
        ]);
    }

    private function createStudent(Branch $branch, array $overrides = []): Student
    {
        return Student::create(array_merge([
            'first_name' => 'طالب',
            'father_name' => 'تجريبي',
            'grandfather_name' => 'أحمد',
            'family_name' => 'علي',
            'mother_name' => 'فاطمة عمر',
            'national_id' => '1' . str_pad((string) rand(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'birth_date' => '2005-01-01',
            'birth_place' => 'طرابلس',
            'gender' => 'MALE',
            'nationality' => 'ليبي',
            'phone' => '091' . rand(1000000, 9999999),
            'guardian_phone' => '092' . rand(1000000, 9999999),
            'branch_id' => $branch->id,
            'current_study_year_id' => $this->studyYear->id,
            'enrolled_academic_year_id' => $this->academicYear->id,
            'academic_year_id' => $this->academicYear->id,
            'department_id' => $this->department->id,
            'academic_status' => 'ENROLLED_ACTIVE',
            'study_type' => 'REGULAR',
            'is_archived' => false,
        ], $overrides));
    }

    public function test_branch_manager_can_archive_student_in_own_branch(): void
    {
        $student = $this->createStudent($this->branch1);

        $response = $this->actingAs($this->branch1Manager)
            ->postJson("/api/v1/students/{$student->id}/archive", [
                'reason' => 'انتقال الطالب أو تجميد القيد بناء على طلبه',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $student->refresh();
        $this->assertTrue((bool) $student->is_archived);
        $this->assertEquals($this->branch1Manager->id, $student->archived_by);
        $this->assertNotNull($student->archived_at);
        $this->assertEquals('انتقال الطالب أو تجميد القيد بناء على طلبه', $student->archive_reason);

        // Verify audit trail logged
        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'STUDENT_ARCHIVED',
            'user_id' => $this->branch1Manager->id,
        ]);
    }

    public function test_branch_manager_cannot_archive_student_in_another_branch(): void
    {
        $student = $this->createStudent($this->branch2);

        $response = $this->actingAs($this->branch1Manager)
            ->postJson("/api/v1/students/{$student->id}/archive", [
                'reason' => 'محاولة غير مصرحة',
            ]);

        // Branch data isolation protects with either 403 Forbidden or 404 Not Found (due to BranchScope)
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));

        $student->refresh();
        $this->assertFalse((bool) $student->is_archived);
    }

    public function test_branch_manager_can_restore_student_in_own_branch(): void
    {
        $student = $this->createStudent($this->branch1, [
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $this->branch1Manager->id,
            'archive_reason' => 'مؤرشف مسبقاً',
        ]);

        $response = $this->actingAs($this->branch1Manager)
            ->postJson("/api/v1/students/{$student->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $student->refresh();
        $this->assertFalse((bool) $student->is_archived);
        $this->assertNull($student->archived_at);
        $this->assertNull($student->archived_by);
        $this->assertNull($student->archive_reason);

        // Verify audit trail logged
        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'STUDENT_RESTORED',
            'user_id' => $this->branch1Manager->id,
        ]);
    }

    public function test_branch_manager_is_strictly_forbidden_from_permanently_deleting_student(): void
    {
        $student = $this->createStudent($this->branch1);

        $response = $this->actingAs($this->branch1Manager)
            ->deleteJson("/api/v1/students/{$student->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
        ]);
    }

    public function test_super_admin_can_permanently_delete_student_and_cascade_data(): void
    {
        $student = $this->createStudent($this->branch1);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('students', [
            'id' => $student->id,
        ]);

        // Verify audit trail logged
        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'STUDENT_DELETED_PERMANENTLY',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_registry_filters_by_archived_status_and_returns_stats(): void
    {
        // 2 Active students in branch1
        $active1 = $this->createStudent($this->branch1, ['is_archived' => false]);
        $active2 = $this->createStudent($this->branch1, ['is_archived' => false]);

        // 1 Archived student in branch1
        $archived1 = $this->createStudent($this->branch1, [
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $this->branch1Manager->id,
            'archive_reason' => 'أرشفة قيد',
        ]);

        // Default query: Active students only (is_archived = 0)
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/students/registry?is_archived=0');

        $response->assertStatus(200);
        $data = $response->json('data');
        $studentIds = collect($data)->pluck('id')->all();
        $this->assertContains($active1->id, $studentIds);
        $this->assertContains($active2->id, $studentIds);
        $this->assertNotContains($archived1->id, $studentIds);

        // Filter archived only: is_archived = 1
        $responseArchived = $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/students/registry?is_archived=1');

        $responseArchived->assertStatus(200);
        $archivedData = $responseArchived->json('data');
        $archivedIds = collect($archivedData)->pluck('id')->all();
        $this->assertContains($archived1->id, $archivedIds);
        $this->assertNotContains($active1->id, $archivedIds);

        // Verify stats in response
        $this->assertGreaterThanOrEqual(1, $response->json('stats.archived'));
    }
}
