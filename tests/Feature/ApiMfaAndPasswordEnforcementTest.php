<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiMfaAndPasswordEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(array $attrs = []): User
    {
        $role = Role::where('name', 'super_admin')->first();

        return User::factory()->create(array_merge(['role_id' => $role->id], $attrs));
    }

    public function test_api_blocks_user_who_must_change_password(): void
    {
        $user = $this->admin(['must_change_password' => true]);

        $this->actingAs($user)->getJson('/api/v1/students')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    public function test_api_blocks_admin_without_mfa_verification_when_mfa_enabled(): void
    {
        config(['auth.mfa_enabled' => true]);
        $user = $this->admin();

        $this->actingAs($user)->getJson('/api/v1/students')
            ->assertStatus(403)
            ->assertJsonPath('code', 'MFA_SETUP_REQUIRED');
    }

    public function test_api_allows_normal_user_when_requirements_met(): void
    {
        config(['auth.mfa_enabled' => false]);

        $this->actingAs($this->admin())->getJson('/api/v1/students')->assertOk();
    }

    public function test_api_session_expires_after_idle_timeout(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->withSession(['last_user_activity' => time() - 1900])
            ->getJson('/api/v1/students')
            ->assertStatus(401)
            ->assertJsonPath('code', 'SESSION_EXPIRED');
    }

    public function test_api_rejects_cross_site_state_changing_requests(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->postJson('/api/v1/bug-reports', [], ['Origin' => 'https://evil.example'])
            ->assertStatus(403)->assertJsonPath('code', 'CROSS_SITE_REQUEST_BLOCKED');

        $this->actingAs($user)->postJson('/api/v1/bug-reports', [], ['Sec-Fetch-Site' => 'cross-site'])
            ->assertStatus(403)->assertJsonPath('code', 'CROSS_SITE_REQUEST_BLOCKED');

        $this->actingAs($user)->postJson('/api/v1/bug-reports', [], ['Origin' => 'http://localhost', 'Sec-Fetch-Site' => 'same-origin'])
            ->assertJsonMissing(['code' => 'CROSS_SITE_REQUEST_BLOCKED']);
    }

    public function test_branch_user_with_users_manage_cannot_escalate_to_super_admin(): void
    {
        $branchRole = Role::where('name', 'branch_registrar')->first();
        $branchRole->permissions()->syncWithoutDetaching(
            \App\Models\Permission::where('code', 'users.manage')->pluck('id')
        );
        $branch = \App\Models\Branch::first();
        $actor = User::factory()->create(['role_id' => $branchRole->id, 'branch_id' => $branch->id, 'must_change_password' => false]);
        $superAdmin = $this->admin(['branch_id' => $branch->id]);
        $superRoleId = Role::where('name', 'super_admin')->value('id');

        $this->actingAs($actor)->postJson('/api/v1/users', [
            'name' => 'x', 'email' => 'x@example.com', 'password' => 'Str0ng#Pass1', 'role_id' => $superRoleId,
        ])->assertStatus(403);

        $this->actingAs($actor)->postJson("/api/v1/users/{$superAdmin->id}/reset-password", [
            'password' => 'Str0ng#Pass1', 'password_confirmation' => 'Str0ng#Pass1',
        ])->assertStatus(403);

        $this->actingAs($actor)->postJson("/api/v1/users/{$actor->id}", [
            'name' => 'a', 'email' => $actor->email, 'role_id' => $superRoleId,
        ])->assertStatus(403);
    }
}
