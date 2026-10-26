<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EmployeePlacement;
use App\Models\JobPosition;
use App\Models\OperationalWindow;
use App\Models\OperationalWindowException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * حذف الحسابات ميزة للمدير العام وحده: بتأكيد كلمة مروره، ولا يُحذف حسابه ولا آخر مدير عام،
 * والحساب المرتبط بسجلات يُرفض حذفه ويُعطَّل بدلاً من ذلك، وكل حذف يُسجَّل في التدقيق.
 */
class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PASSWORD = 'Admin-Pass-2026!';

    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $this->admin->forceFill(['password' => Hash::make(self::ADMIN_PASSWORD)])->save();
        $this->branch = Branch::where('code', '01')->firstOrFail();
    }

    private function victim(string $roleName = 'branch_registrar'): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'branch_id' => $this->branch->id,
            'must_change_password' => false,
        ]);
    }

    private function removeAccount(User $actor, User $target, ?string $password = self::ADMIN_PASSWORD)
    {
        return $this->actingAs($actor)->deleteJson("/api/v1/users/{$target->id}", $password === null ? [] : ['password' => $password]);
    }

    public function test_super_admin_deletes_an_account_and_everything_that_hangs_on_it(): void
    {
        $victim = $this->victim();
        $victim->directPermissions()->attach(Permission::where('code', 'students.view')->firstOrFail()->id, ['is_granted' => true]);
        EmployeePlacement::create([
            'user_id' => $victim->id, 'job_position_id' => JobPosition::first()?->id ?? JobPosition::create(['code' => 'X-1', 'title' => 'صفة', 'level_order' => 1, 'is_active' => true])->id,
            'branch_id' => $this->branch->id, 'start_date' => '2026-09-01', 'is_current' => true, 'status' => 'active',
        ]);
        DB::table('sessions')->insert(['id' => 'sess-victim', 'user_id' => $victim->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => time()]);

        $this->removeAccount($this->admin, $victim)->assertOk()->assertJsonPath('success', true);

        $this->assertNull(User::find($victim->id));
        $this->assertSame(0, DB::table('user_permissions')->where('user_id', $victim->id)->count());
        $this->assertSame(0, EmployeePlacement::where('user_id', $victim->id)->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $victim->id)->count());

        $log = SystemAuditTrail::where('event_type', 'USER_DELETED')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame($victim->email, $log->payload['email'] ?? null);
    }

    public function test_the_admin_password_is_required_and_must_be_right(): void
    {
        $victim = $this->victim();

        $this->removeAccount($this->admin, $victim, null)->assertStatus(422);
        $this->removeAccount($this->admin, $victim, 'wrong-password')->assertStatus(422)->assertJsonPath('success', false);

        $this->assertNotNull(User::find($victim->id));
        $this->assertFalse(SystemAuditTrail::where('event_type', 'USER_DELETED')->exists());
    }

    public function test_only_the_super_admin_may_delete_even_with_the_users_manage_permission(): void
    {
        $manager = $this->victim('branch_manager');
        $manager->forceFill(['password' => Hash::make('Manager-Pass-1!')])->save();
        $manager->directPermissions()->attach(Permission::where('code', 'users.manage')->firstOrFail()->id, ['is_granted' => true]);
        $target = $this->victim();

        $this->actingAs($manager->fresh())->deleteJson("/api/v1/users/{$target->id}", ['password' => 'Manager-Pass-1!'])
            ->assertStatus(403);

        $this->assertNotNull(User::find($target->id));
        $this->assertTrue(SystemAuditTrail::where('event_type', 'UNAUTHORIZED_ACCESS_ATTEMPT')->where('user_id', $manager->id)->exists());
    }

    public function test_a_user_without_the_permission_is_refused_by_the_route(): void
    {
        $registrar = $this->victim();
        $target = $this->victim();

        $this->actingAs($registrar)->deleteJson("/api/v1/users/{$target->id}", ['password' => 'x'])->assertForbidden();
        $this->assertNotNull(User::find($target->id));
    }

    public function test_the_super_admin_cannot_delete_the_own_account(): void
    {
        $this->removeAccount($this->admin, $this->admin)->assertStatus(422);

        $this->assertNotNull(User::find($this->admin->id));
    }

    public function test_a_second_super_admin_can_be_deleted_but_the_last_one_never(): void
    {
        $second = $this->victim('super_admin');
        $second->forceFill(['branch_id' => null])->save();
        $this->assertSame(2, User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->count());

        $this->removeAccount($this->admin, $second)->assertOk();
        $this->assertNull(User::find($second->id));

        $this->assertSame(1, User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->count());
        $this->removeAccount($this->admin, $this->admin)->assertStatus(422);
        $this->assertNotNull(User::find($this->admin->id));
    }

    public function test_an_account_tied_to_records_is_refused_and_stays_intact(): void
    {
        $victim = $this->victim('branch_manager');
        $window = OperationalWindow::firstOrFail();
        OperationalWindowException::create([
            'window_id' => $window->id, 'branch_id' => $this->branch->id, 'granted_by' => $victim->id,
            'extended_until' => now()->addDays(3), 'reason' => 'تمديد مرتبط بالحساب',
        ]);

        $response = $this->removeAccount($this->admin, $victim);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('عطّل', $response->json('message'));
        $this->assertNotNull(User::find($victim->id));
        $this->assertFalse(SystemAuditTrail::where('event_type', 'USER_DELETED')->exists());
    }

    public function test_ordinary_dashboard_traffic_does_not_use_up_the_deletion_limit(): void
    {
        $victim = $this->victim();

        // A busy session: far more than ten ordinary requests in the same minute.
        foreach (range(1, 25) as $i) {
            $this->actingAs($this->admin)->getJson('/api/v1/users')->assertOk();
        }

        $this->removeAccount($this->admin, $victim)->assertOk();
        $this->assertNull(User::find($victim->id));
    }

    public function test_repeated_deletion_attempts_are_limited_to_ten_a_minute(): void
    {
        $target = $this->victim();

        foreach (range(1, 10) as $i) {
            $this->removeAccount($this->admin, $target, 'wrong-password')->assertStatus(422);
        }

        $this->removeAccount($this->admin, $target, 'wrong-password')->assertStatus(429);
        $this->assertNotNull(User::find($target->id));
    }
    public function test_the_delete_button_is_rendered_only_for_the_super_admin(): void
    {
        $view = file_get_contents(resource_path('views/dashboard/sections/users.blade.php'));

        $pos = strpos($view, '@click="deleteUser(u)"');
        $this->assertNotFalse($pos);
        $before = substr($view, 0, $pos);
        $this->assertGreaterThan(strrpos($before, '@endif'), strrpos($before, "@if(auth()->user()?->isSuperAdmin())"));
        $this->assertStringContainsString('async deleteUser(u)', file_get_contents(resource_path('views/dashboard/modals/scripts.blade.php')));
    }
}
