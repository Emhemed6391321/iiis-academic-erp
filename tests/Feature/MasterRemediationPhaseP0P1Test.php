<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudyYear;
use App\Models\Department;
use App\Models\Course;
use App\Models\GradeBatch;
use App\Models\StudentGrade;
use App\Models\StudentAttendance;
use App\Models\OperationalWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MasterRemediationPhaseP0P1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_branch_user_cannot_save_grades_for_another_branch(): void
    {
        $branch1 = Branch::firstOrCreate(['id' => 1], ['name' => 'فرع طرابلس', 'code' => 'TIP', 'is_active' => true]);
        $branch2 = Branch::firstOrCreate(['id' => 2], ['name' => 'فرع بنغازي', 'code' => 'BEN', 'is_active' => true]);

        $role = Role::firstOrCreate(['name' => 'branch_employee'], ['display_name' => 'موظف فرع', 'is_global' => false]);
        $user = User::factory()->create([
            'branch_id' => $branch1->id,
            'role_id' => $role->id,
        ]);

        $year = AcademicYear::where('is_current', true)->first();
        if (!$year) {
            $year = AcademicYear::create([
                'name' => '2026/2027',
                'code' => '2026-2027',
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'is_current' => true,
                'is_locked' => false,
            ]);
        }

        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $dept = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SHR']);

        $batchForBranch2 = GradeBatch::create([
            'batch_code' => 'TEST-BATCH-BRANCH-2-' . uniqid(),
            'branch_id' => $branch2->id,
            'academic_year_id' => $year->id,
            'study_year_id' => $studyYear->id,
            'department_id' => $dept->id,
            'semester' => 1,
            'status' => 'DRAFT',
        ]);

        $grade = StudentGrade::create([
            'grade_batch_id' => $batchForBranch2->id,
            'student_id' => 1,
            'course_id' => 1,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/grades/batch-save', [
                'batch_id' => $batchForBranch2->id,
                'grades' => [
                    ['id' => $grade->id, 'coursework' => 20]
                ],
            ]);

        // Returns 404 because BranchScope completely hides branch 2 records from branch 1 users
        $response->assertStatus(404);
    }

    public function test_non_hq_user_cannot_execute_progression(): void
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'فرع مصراتة', 'code' => 'MRA', 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'branch_manager'], ['display_name' => 'مدير فرع', 'is_global' => false]);
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);

        $year1 = AcademicYear::firstOrCreate(['code' => '2025-2026'], [
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => false,
        ]);

        $year2 = AcademicYear::firstOrCreate(['code' => '2026-2027'], [
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/settings/progression/execute', [
                'from_academic_year_id' => $year1->id,
                'to_academic_year_id' => $year2->id,
                'confirm_rollover' => true,
            ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('إجراء محظور', $response->json('message'));
    }

    public function test_import_batch_rejects_duplicate_national_id_inside_same_file(): void
    {
        $branch = Branch::first();
        $user = User::factory()->create([
            'branch_id' => $branch ? $branch->id : 1,
        ]);

        $dupNationalId = '119999888877';

        $students = [
            [
                'first_name' => 'محمد',
                'father_name' => 'علي',
                'grandfather_name' => 'سالم',
                'family_name' => 'المبروك',
                'mother_name' => 'فاطمة',
                'national_id' => $dupNationalId,
                'birth_date' => '2005-04-12',
                'gender' => 'ذكر',
            ],
            [
                'first_name' => 'خالد',
                'father_name' => 'سالم',
                'grandfather_name' => 'عمر',
                'family_name' => 'المبروك',
                'mother_name' => 'عائشة',
                'national_id' => $dupNationalId,
                'birth_date' => '2006-08-20',
                'gender' => 'ذكر',
            ],
        ];

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/v1/students/import-batch', [
                'students' => $students,
            ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['imported_count']);
        $this->assertGreaterThanOrEqual(1, count($data['errors']));
        $this->assertStringContainsString('مكرر داخل نفس الملف', $data['errors'][0]['error']);
    }
}
