<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\StudyYear;

class BranchDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_branch_user_only_sees_students_from_own_branch()
    {
        $branchTripoli = Branch::where('code', '01')->first();
        $branchBenghazi = Branch::where('code', '02')->first();
        $currentYear = AcademicYear::where('is_current', true)->first();
        $dept = Department::first();
        $studyYear = StudyYear::first();

        // Create student in Tripoli
        $stdTripoli = Student::withoutGlobalScopes()->create([
            'national_id' => '119900000001',
            'branch_id' => $branchTripoli->id,
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'first_name' => 'طارق',
            'father_name' => 'فرج',
            'grandfather_name' => 'علي',
            'family_name' => 'الطرابلسي',
            'mother_name' => 'فاطمة',
            'gender' => 'MALE',
            'birth_date' => '2005-05-01',
            'birth_place' => 'طرابلس',
            'phone' => '091-1111111',
            'guardian_phone' => '091-2222222',
            'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => $currentYear->id,
        ]);

        // Create student in Benghazi
        $stdBenghazi = Student::withoutGlobalScopes()->create([
            'national_id' => '119900000002',
            'branch_id' => $branchBenghazi->id,
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'first_name' => 'أحمد',
            'father_name' => 'سالم',
            'grandfather_name' => 'عمر',
            'family_name' => 'البرغثي',
            'mother_name' => 'مريم',
            'gender' => 'MALE',
            'birth_date' => '2005-06-01',
            'birth_place' => 'بنغازي',
            'phone' => '092-3333333',
            'guardian_phone' => '092-4444444',
            'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => $currentYear->id,
        ]);

        // Acting as Tripoli Branch Registrar
        $tripoliRegistrar = User::where('email', 'registrar.tip@iiis.sch.ly')->first();

        $response = $this->actingAs($tripoliRegistrar)->getJson('/api/v1/students');
        $response->assertStatus(200);

        $studentIds = collect($response->json('data.data'))->pluck('id')->toArray();

        $this->assertContains($stdTripoli->id, $studentIds);
        $this->assertNotContains($stdBenghazi->id, $studentIds);

        // Acting as Super Admin (HQ) - Sees both branches
        $superAdmin = User::where('email', 'admin@iiis.sch.ly')->first();
        $hqResponse = $this->actingAs($superAdmin)->getJson('/api/v1/students');
        $hqResponse->assertStatus(200);

        $hqStudentIds = collect($hqResponse->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($stdTripoli->id, $hqStudentIds);
        $this->assertContains($stdBenghazi->id, $hqStudentIds);
    }
}
