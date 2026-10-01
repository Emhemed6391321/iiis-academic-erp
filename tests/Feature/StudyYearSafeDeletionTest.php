<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use App\Services\StudyYearDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyYearSafeDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_cannot_delete_academic_year_with_associated_students_or_data(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $currentYear = AcademicYear::where('is_current', true)->first();
        $this->assertNotNull($currentYear);

        // Try to delete current year or year with students
        $res = $this->actingAs($user)->deleteJson("/api/v1/settings/years/{$currentYear->id}");
        $res->assertStatus(422)
            ->assertJson(['status' => 'error']);

        $this->assertDatabaseHas('academic_years', [
            'id' => $currentYear->id,
        ]);
    }

    public function test_inspect_year_returns_accurate_dependency_report(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $currentYear = AcademicYear::where('is_current', true)->first();

        $res = $this->actingAs($user)->getJson("/api/v1/settings/years/{$currentYear->id}/inspect");
        $res->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'can_delete' => false,
                ]
            ]);
    }

    public function test_can_safely_delete_new_empty_year(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Create an empty upcoming year with 0 students and 0 grades
        $emptyYear = AcademicYear::create([
            'code' => '2030-2031',
            'name' => '2030/2031',
            'start_date' => '2030-09-01',
            'end_date' => '2031-06-30',
            'is_current' => false,
            'is_locked' => false,
            'status' => 'DRAFT',
        ]);

        $inspectRes = $this->actingAs($user)->getJson("/api/v1/settings/years/{$emptyYear->id}/inspect");
        $inspectRes->assertStatus(200)
            ->assertJsonPath('data.can_delete', true);

        $res = $this->actingAs($user)->deleteJson("/api/v1/settings/years/{$emptyYear->id}");
        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('academic_years', [
            'id' => $emptyYear->id,
        ]);
    }
}
