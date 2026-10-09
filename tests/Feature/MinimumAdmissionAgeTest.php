<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\Branch;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;

class MinimumAdmissionAgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        // Seeded accounts require a password change, which the API now enforces.
        \App\Models\User::query()->update(['must_change_password' => false]);
    }

    public function test_student_younger_than_15_years_is_rejected()
    {
        $branch = Branch::first();
        $dept = Department::first();
        $studyYear = StudyYear::first();
        $admin = User::first();

        // Reference academic year starts 2026-09-01. A student born 2012-10-01 is only 13 years old.
        $underageDate = '2012-10-01';

        $response = $this->actingAs($admin)->postJson('/api/v1/students', [
            'national_id' => '120120000099',
            'first_name' => 'محمد',
            'father_name' => 'علي',
            'grandfather_name' => 'أحمد',
            'family_name' => 'الورفلي',
            'mother_name' => 'فاطمة محمود',
            'gender' => 'MALE',
            'birth_date' => $underageDate,
            'birth_place' => 'طرابلس',
            'phone' => '091-1112233',
            'guardian_phone' => '091-2223344',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['birth_date']);
    }

    public function test_student_aged_15_or_older_is_accepted()
    {
        $branch = Branch::first();
        $dept = Department::first();
        $studyYear = StudyYear::first();
        $admin = User::first();

        // Born 2010-01-01 -> age > 16 on 2026-09-01.
        $validBirthDate = '2010-01-01';

        $response = $this->actingAs($admin)->postJson('/api/v1/students', [
            'national_id' => '120100000088',
            'first_name' => 'عبد الله',
            'father_name' => 'سالم',
            'grandfather_name' => 'إبراهيم',
            'family_name' => 'الترهوني',
            'mother_name' => 'عائشة محمد',
            'gender' => 'MALE',
            'birth_date' => $validBirthDate,
            'birth_place' => 'طرابلس',
            'phone' => '091-3334455',
            'guardian_phone' => '091-5556677',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('students', [
            'national_id' => '120100000088',
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);
    }
}
