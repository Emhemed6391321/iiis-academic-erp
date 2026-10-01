<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Role;
use App\Models\StudentStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentEditAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_update_student_personal_and_civil_data()
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'طرابلس', 'code' => '01', 'city' => 'طرابلس', 'is_active' => true]);
        $dept = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SH']);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'year_number' => 1]);
        $academicYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::create(['name' => '2026/2027', 'code' => '2026', 'is_current' => true]);

        $student = Student::create([
            'academic_number' => '1260001',
            'national_id' => '119950000011',
            'first_name' => 'أحمد',
            'father_name' => 'محمد',
            'grandfather_name' => 'علي',
            'family_name' => 'الطرابلسي',
            'mother_name' => 'فاطمة سالم',
            'gender' => 'MALE',
            'birth_date' => '1995-05-15',
            'birth_place' => 'طرابلس',
            'nationality' => 'ليبي',
            'phone' => '091-1111111',
            'guardian_phone' => '092-2222222',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch->id,
            'enrolled_academic_year_id' => $academicYear->id,
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);

        $hqRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير عام', 'scope_type' => 'GLOBAL_SCOPE']);
        $admin = User::factory()->create(['role_id' => $hqRole->id, 'branch_id' => null]);
        $this->actingAs($admin);

        $updateData = [
            'national_id' => '119950000011',
            'first_name' => 'أحمد المعدل',
            'father_name' => 'محمد',
            'grandfather_name' => 'علي',
            'family_name' => 'الزاوية',
            'mother_name' => 'فاطمة سالم المبروك',
            'gender' => 'MALE',
            'birth_date' => '1995-05-15',
            'birth_place' => 'الزاوية',
            'nationality' => 'ليبي',
            'religion' => 'مسلم',
            'passport_number' => 'C9876543',
            'phone' => '091-9998877',
            'guardian_phone' => '092-8887766',
            'guardian_name' => 'محمد علي الطرابلسي',
            'guardian_relationship' => 'والد',
            'emergency_contact' => '091-5554433',
            'address' => 'طرابلس - طريق الشط',
            'email' => 'ahmed.updated@example.com',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'INTISAB',
            'ministry_student_id' => 'MIN-2026-9988',
            'has_disability' => false,
            'blood_type' => 'O+',
            'health_status' => 'سليم',
            'notes' => 'تم التحديث بموجب وثائق الرقم الوطني الجديدة',
        ];

        $response = $this->putJson("/api/v1/students/{$student->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('student.first_name', 'أحمد المعدل');
        $response->assertJsonPath('student.family_name', 'الزاوية');
        $response->assertJsonPath('student.passport_number', 'C9876543');
        $response->assertJsonPath('student.ministry_student_id', 'MIN-2026-9988');
        $response->assertJsonPath('student.study_type', 'INTISAB');

        // Check timeline history event
        $this->assertDatabaseHas('student_status_history', [
            'student_id' => $student->id,
            'event_type' => 'DATA_UPDATE',
        ]);
    }

    public function test_branch_user_cannot_update_student_from_another_branch()
    {
        $branch1 = Branch::first() ?? Branch::create(['name' => 'فرع طرابلس', 'code' => 'B91', 'city' => 'طرابلس', 'is_active' => true]);
        $branch2 = Branch::skip(1)->first() ?? Branch::create(['name' => 'فرع بنغازي', 'code' => 'B92', 'city' => 'بنغازي', 'is_active' => true]);
        $dept = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SH2']);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'year_number' => 1]);
        $academicYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::create(['name' => '2026/2027', 'code' => '2026', 'is_current' => true]);

        // Student belongs to branch 2
        $studentInBranch2 = Student::create([
            'academic_number' => '1260002',
            'national_id' => '119960000022',
            'first_name' => 'سالم',
            'father_name' => 'عمر',
            'grandfather_name' => 'خالد',
            'family_name' => 'البنغازي',
            'mother_name' => 'مريم',
            'gender' => 'MALE',
            'birth_date' => '1996-03-20',
            'birth_place' => 'بنغازي',
            'phone' => '091-2223344',
            'guardian_phone' => '092-3334455',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch2->id,
            'enrolled_academic_year_id' => $academicYear->id,
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);

        // User belongs to branch 1
        $branchRole = Role::firstOrCreate(['name' => 'branch_registrar'], ['display_name' => 'مسجل الفرع', 'scope_type' => 'BRANCH_SCOPE']);
        $branchUser = User::factory()->create(['role_id' => $branchRole->id, 'branch_id' => $branch1->id]);
        $this->actingAs($branchUser);

        $updateData = [
            'national_id' => '119960000022',
            'first_name' => 'سالم المعدل',
            'father_name' => 'عمر',
            'grandfather_name' => 'خالد',
            'family_name' => 'البنغازي',
            'mother_name' => 'مريم',
            'gender' => 'MALE',
            'birth_date' => '1996-03-20',
            'birth_place' => 'بنغازي',
            'phone' => '091-2223344',
            'guardian_phone' => '092-3334455',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
        ];

        $response = $this->putJson("/api/v1/students/{$studentInBranch2->id}", $updateData);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
