<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Branch;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * نطاق عرض البيانات لمدير الفرع: لا تظهر له أرقام أو بلاغات تخص فروعاً أخرى.
 */
class BranchManagerDataScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $this->superAdmin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
    }

    public function test_suspended_branch_count_on_manager_dashboard_is_limited_to_own_branch(): void
    {
        // فرع آخر موقوف ولا يجب أن يظهر في لوحة مدير فرع طرابلس
        Branch::where('code', '02')->update(['branch_status' => 'SUSPENDED']);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/hq/dashboard?refresh=1')
            ->assertOk()
            ->assertJsonPath('kpis.suspended_branches', 0);

        $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/hq/dashboard?refresh=1')
            ->assertOk()
            ->assertJsonPath('kpis.suspended_branches', 1);
    }

    public function test_suspended_branch_count_on_manager_dashboard_counts_own_branch_when_suspended(): void
    {
        Branch::where('code', '01')->update(['branch_status' => 'SUSPENDED']);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/hq/dashboard?refresh=1')
            ->assertOk()
            ->assertJsonPath('kpis.suspended_branches', 1);
    }

    public function test_manager_sees_only_own_bug_reports_and_stats(): void
    {
        $this->submitBugReport($this->superAdmin, 'بلاغ من الإدارة العامة');
        $this->submitBugReport($this->manager, 'بلاغ من مدير الفرع');

        $response = $this->actingAs($this->manager)
            ->getJson('/api/v1/bug-reports')
            ->assertOk();

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertSame(['بلاغ من مدير الفرع'], $titles);
        $this->assertSame(1, $response->json('stats.total'));
    }

    public function test_system_monitor_sees_all_bug_reports(): void
    {
        $this->submitBugReport($this->superAdmin, 'بلاغ من الإدارة العامة');
        $this->submitBugReport($this->manager, 'بلاغ من مدير الفرع');

        $this->actingAs($this->superAdmin)
            ->getJson('/api/v1/bug-reports')
            ->assertOk()
            ->assertJsonPath('stats.total', 2);
    }

    private function submitBugReport(User $user, string $title): void
    {
        $this->actingAs($user)->postJson('/api/v1/bug-reports', [
            'section_key' => 'students',
            'section_name' => 'سجل الطلاب',
            'category' => 'ui',
            'rating' => 4,
            'title' => $title,
            'description' => 'وصف بلاغ اختباري كافٍ للتحقق',
        ])->assertCreated();
    }
}
