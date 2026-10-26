<?php

namespace Tests\Feature;

use App\Models\JobPosition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفة وظيفية يضيفها المسؤول من «الصفات والمسميات الوظيفية المعتمدة» ليست دوراً:
 * لا يُنشأ لها دور ولا ترتبط بدور، ولا تظهر في قائمة أدوار المستخدم الجديد ولا في مصفوفة الصلاحيات.
 */
class JobPositionIsNotARoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_added_position_creates_no_role_and_is_not_linked_to_one(): void
    {
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $rolesBefore = Role::count();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/settings/job-position/save', [
            'code' => 'UNIT_TEST_POS', 'title' => 'وحدة اختبارية', 'level_order' => 4,
        ])->assertOk();

        $position = JobPosition::where('code', 'UNIT_TEST_POS')->firstOrFail();
        $this->assertNull($position->role_id, 'An added position must not carry the role badge.');
        $this->assertNull($response->json('position.role_id'));
        $this->assertSame($rolesBefore, Role::count());
        $this->assertFalse(Role::where('display_name', 'وحدة اختبارية')->exists());

        // The new-user form's role list and the permission matrix are built from roles only.
        $roles = $this->actingAs($admin)->getJson('/api/v1/users')->assertOk()->json('roles');
        $this->assertNotContains('وحدة اختبارية', array_column($roles, 'display_name'));

        // It is offered in the new-user form's position list, which is what the form refreshes when it opens.
        $positions = $this->actingAs($admin)->getJson('/api/v1/users')->json('job_positions');
        $this->assertContains('UNIT_TEST_POS', array_column($positions, 'code'));

        $matrix = $this->actingAs($admin)->getJson('/api/v1/permissions/matrix')->assertOk();
        $this->assertStringNotContainsString('وحدة اختبارية', $matrix->getContent());
    }
}
