<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWorkflowExportAndSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_transfers_export_returns_csv_stream()
    {
        $branch1 = Branch::firstOrCreate(['id' => 1], ['name' => 'فرع طرابلس', 'code' => 'TIP', 'is_active' => true]);
        $branch2 = Branch::firstOrCreate(['id' => 2], ['name' => 'فرع بنغازي', 'code' => 'BEN', 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get('/api/v1/student-workflow/transfers/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_branch_manager_can_submit_status_request_for_own_student()
    {
        $branch = Branch::firstOrCreate(['id' => 1], ['name' => 'فرع طرابلس', 'code' => 'TIP', 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'branch_manager'], ['display_name' => 'مدير فرع', 'is_global' => false]);
        $manager = User::factory()->create(['role_id' => $role->id, 'branch_id' => $branch->id]);

        $student = Student::first();
        $student->update(['branch_id' => $branch->id]);

        $payload = [
            'track' => 'status',
            'student_id' => $student->id,
            'request_type' => 'PAUSE',
            'reason' => 'ظروف أسرية قاهرة تستدعي الإيقاف المؤقت',
        ];

        $response = $this->actingAs($manager)->postJson('/api/v1/student-workflow/submit', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('enrollment_status_requests', [
            'student_id' => $student->id,
            'request_type' => 'PAUSE',
            'branch_status' => 'APPROVED',
            'hq_status' => 'PENDING',
        ]);
    }

    public function test_branch_manager_cannot_submit_request_for_student_in_different_branch()
    {
        $branch1 = Branch::firstOrCreate(['id' => 1], ['name' => 'فرع طرابلس', 'code' => 'TIP', 'is_active' => true]);
        $branch2 = Branch::firstOrCreate(['id' => 2], ['name' => 'فرع بنغازي', 'code' => 'BEN', 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'branch_manager'], ['display_name' => 'مدير فرع', 'is_global' => false]);
        $manager = User::factory()->create(['role_id' => $role->id, 'branch_id' => $branch1->id]);

        $student = Student::first();
        $student->update(['branch_id' => $branch2->id]);

        $payload = [
            'track' => 'status',
            'student_id' => $student->id,
            'request_type' => 'PAUSE',
            'reason' => 'محاولة غير مصرح بها',
        ];

        $response = $this->actingAs($manager)->postJson('/api/v1/student-workflow/submit', $payload);

        // ModelNotFoundException (404) is thrown because BranchScope isolates student from other branches
        $response->assertStatus(404);
    }
}
