<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * عدد العاملين بالفرع (الموظفون والمعلمون) يُدخل عند إضافة الفرع أو تعديله، ويظهر في الدليل وتفاصيل الفرع.
 * إن كان الحقل فارغاً يُعرض العدد المحسوب من الحسابات والتسكينات.
 */
class BranchOperationalStaffPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $role->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'فرع الزاوية',
            'code' => 'ZAW-1',
            'city' => 'الزاوية',
            'branch_status' => 'ACTIVE',
        ], $overrides);
    }

    public function test_new_branch_stores_the_planned_admin_and_academic_staff(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/branches', $this->payload([
            'operational_admin_staff' => 6,
            'operational_academic_staff' => 12,
        ]))->assertStatus(201);

        $this->assertDatabaseHas('branches', [
            'code' => 'ZAW-1',
            'operational_admin_staff' => 6,
            'operational_academic_staff' => 12,
        ]);
    }

    public function test_planned_staff_is_optional_and_negative_numbers_are_refused(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/branches', $this->payload(['code' => 'ZAW-2']))
            ->assertStatus(201);
        $this->assertDatabaseHas('branches', ['code' => 'ZAW-2', 'operational_admin_staff' => null]);

        $this->actingAs($this->admin)->postJson('/api/v1/branches', $this->payload([
            'code' => 'ZAW-3',
            'operational_academic_staff' => -1,
        ]))->assertStatus(422)->assertJsonValidationErrors(['operational_academic_staff']);
    }

    public function test_staff_count_can_be_edited(): void
    {
        $branch = Branch::create([
            'name' => 'فرع', 'code' => 'ZAW-4', 'city' => 'الزاوية', 'branch_status' => 'ACTIVE', 'is_active' => true,
            'operational_admin_staff' => 2,
        ]);

        $this->actingAs($this->admin)->putJson("/api/v1/branches/{$branch->id}", [
            'operational_admin_staff' => 9,
            'operational_academic_staff' => 4,
        ])->assertOk();

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'operational_admin_staff' => 9,
            'operational_academic_staff' => 4,
        ]);
    }

    public function test_directory_shows_the_entered_count_and_falls_back_to_accounts_when_empty(): void
    {
        $instructorRole = Role::create(['name' => 'course_instructor', 'display_name' => 'عضو هيئة تدريس', 'scope_type' => 'BRANCH_SCOPE']);

        $entered = Branch::create([
            'name' => 'فرع بإدخال', 'code' => 'ENT-1', 'city' => 'مصراتة', 'branch_status' => 'ACTIVE', 'is_active' => true,
            'operational_admin_staff' => 5, 'operational_academic_staff' => 9,
        ]);
        $empty = Branch::create([
            'name' => 'فرع بلا إدخال', 'code' => 'ENT-2', 'city' => 'زليتن', 'branch_status' => 'ACTIVE', 'is_active' => true,
        ]);
        User::factory()->count(2)->create(['role_id' => $instructorRole->id, 'branch_id' => $empty->id]);

        $list = collect($this->actingAs($this->admin)->getJson('/api/v1/branches/directory')->assertOk()->json('data'));

        $shownEntered = $list->firstWhere('id', $entered->id);
        $this->assertSame(9, $shownEntered['academic_staff']);
        $this->assertSame(5, $shownEntered['admin_staff']);
        $this->assertSame(14, $shownEntered['total_staff']);

        // Nothing entered: the count comes from the two instructor accounts on that branch.
        $shownEmpty = $list->firstWhere('id', $empty->id);
        $this->assertSame(2, $shownEmpty['academic_staff']);
        $this->assertSame(0, $shownEmpty['admin_staff']);
        $this->assertSame(2, $shownEmpty['total_staff']);
    }
}
