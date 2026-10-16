<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\JobPosition;
use App\Models\EmployeePlacement;
use App\Models\OfficialDocumentSignatory;
use App\Services\AdminSettingsService;
use App\Services\BranchStaffService;

class RoleTitleAndStaffCountTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperAdmin(): User
    {
        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_role_title_edited_in_matrix_is_the_position_title_everywhere()
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'branch_manager', 'display_name' => 'مدير المعهد (الفرع)', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();

        $response = $this->actingAs($admin)->putJson("/api/v1/permissions/roles/{$role->id}/title", [
            'display_name' => 'مدير الفرع المعتمد',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'display_name' => 'مدير الفرع المعتمد']);
        $this->assertDatabaseHas('job_positions', ['role_id' => $role->id, 'title' => 'مدير الفرع المعتمد']);
    }

    public function test_title_edited_in_settings_updates_the_role_too()
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'branch_registrar', 'display_name' => 'قسم شؤون الطلبة', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();
        $position = JobPosition::where('role_id', $role->id)->firstOrFail();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/settings/job-position/save', [
            'id' => $position->id,
            'code' => $position->code,
            'title' => 'قسم شؤون الطلبة بالفرع',
            'level_order' => 2,
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'display_name' => 'قسم شؤون الطلبة بالفرع']);
    }

    public function test_position_linked_to_a_role_cannot_be_deleted()
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'branch_social_worker', 'display_name' => 'أخصائي اجتماعي', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();
        $position = JobPosition::where('role_id', $role->id)->firstOrFail();

        $this->actingAs($admin)->deleteJson("/api/v1/admin/settings/job-position/{$position->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('job_positions', ['id' => $position->id]);
    }

    public function test_hidden_name_keeps_the_title_on_the_signed_document()
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create(['name' => 'branch_registrar', 'display_name' => 'مسجل شؤون الطلبة', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();
        $position = JobPosition::where('role_id', $role->id)->firstOrFail();
        $branch = Branch::create(['name' => 'فرع اختبار', 'code' => 'TST-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);
        $employee = User::factory()->create(['role_id' => $role->id, 'branch_id' => $branch->id]);

        EmployeePlacement::create([
            'user_id' => $employee->id,
            'job_position_id' => $position->id,
            'branch_id' => $branch->id,
            'start_date' => '2026-09-01',
            'is_current' => true,
            'status' => 'active',
            'show_name_on_documents' => false,
        ]);
        OfficialDocumentSignatory::create([
            'document_code' => 'enrollment_cert',
            'slot_key' => 'prepared_by',
            'slot_label' => 'مسجل شؤون الطلاب',
            'job_position_id' => $position->id,
            'is_active' => true,
        ]);
        AdminSettingsService::clearCache();

        $slot = AdminSettingsService::getSignatoriesFor('enrollment_cert', $branch->id)['prepared_by'];
        $this->assertSame('', $slot['name']);
        $this->assertSame('مسجل شؤون الطلبة', $slot['title']);

        EmployeePlacement::query()->update(['show_name_on_documents' => true]);
        AdminSettingsService::clearCache();

        $slot = AdminSettingsService::getSignatoriesFor('enrollment_cert', $branch->id)['prepared_by'];
        $this->assertSame($employee->name, $slot['name']);
    }

    public function test_branch_staff_is_computed_from_real_accounts_not_stored_numbers()
    {
        $admin = $this->createSuperAdmin();
        $instructorRole = Role::create(['name' => 'course_instructor', 'display_name' => 'أستاذ مقرر', 'scope_type' => 'BRANCH_SCOPE']);
        $adminRole = Role::create(['name' => 'branch_admin_fin', 'display_name' => 'الشؤون المالية', 'scope_type' => 'BRANCH_SCOPE']);

        $branch = Branch::create([
            'name' => 'فرع الكادر',
            'code' => 'STF-01',
            'city' => 'طرابلس',
            'branch_status' => 'ACTIVE',
            'total_staff' => 42,
            'academic_staff' => 27,
            'admin_staff' => 14,
        ]);
        User::factory()->create(['role_id' => $instructorRole->id, 'branch_id' => $branch->id]);
        User::factory()->create(['role_id' => $adminRole->id, 'branch_id' => $branch->id]);

        $this->assertSame(
            ['total' => 2, 'academic' => 1, 'admin' => 1],
            BranchStaffService::forBranch($branch->id)
        );

        $response = $this->actingAs($admin)->getJson('/api/v1/branches/directory');
        $response->assertStatus(200);
        $row = collect($response->json('data'))->firstWhere('id', $branch->id);
        $this->assertSame(2, $row['total_staff']);
        $this->assertSame(1, $row['academic_staff']);
        $this->assertSame(1, $row['admin_staff']);
    }
}
