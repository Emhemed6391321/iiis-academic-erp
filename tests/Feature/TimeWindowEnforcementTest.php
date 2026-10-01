<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\OperationalWindow;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\User;
use App\Models\Department;
use App\Models\StudyYear;
use Carbon\Carbon;

class TimeWindowEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_registration_blocked_when_registration_window_is_closed()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $branch = Branch::first();
        $dept = Department::first();
        $studyYear = StudyYear::first();
        $tripoliRegistrar = User::where('email', 'registrar.tip@iiis.sch.ly')->first();

        // Expire the registration window
        OperationalWindow::where('window_type', 'REGISTRATION')->update([
            'start_at' => Carbon::now()->subDays(30),
            'end_at' => Carbon::now()->subDays(2), // ended 2 days ago
        ]);

        $response = $this->actingAs($tripoliRegistrar)->postJson('/api/v1/students', [
            'national_id' => '120090000077',
            'first_name' => 'خالد',
            'father_name' => 'مصطفى',
            'grandfather_name' => 'صالح',
            'family_name' => 'المصراتي',
            'mother_name' => 'حليمة',
            'gender' => 'MALE',
            'birth_date' => '2009-02-15',
            'birth_place' => 'طرابلس',
            'phone' => '091-8889900',
            'guardian_phone' => '091-9990011',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('انتهت الفترة المحددة', $response->json('message'));
    }
}
