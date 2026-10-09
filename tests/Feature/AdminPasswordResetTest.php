<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_reset_forces_password_change_and_ends_target_sessions(): void
    {
        $role = Role::where('name', 'super_admin')->first();
        $admin = User::factory()->create(['role_id' => $role->id, 'must_change_password' => false]);
        $target = User::factory()->create(['role_id' => $role->id, 'must_change_password' => false]);

        config(['session.driver' => 'database']); // production driver; phpunit.xml uses array
        DB::table('sessions')->insert([
            'id' => 'victim-session', 'user_id' => $target->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit', 'payload' => '', 'last_activity' => time(),
        ]);

        $this->actingAs($admin)->postJson("/api/v1/users/{$target->id}/reset-password", [
            'password' => 'Str0ng#Pass1', 'password_confirmation' => 'Str0ng#Pass1',
        ])->assertOk();

        $target->refresh();
        $this->assertTrue(Hash::check('Str0ng#Pass1', $target->password));
        $this->assertTrue((bool) $target->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'victim-session']);
    }

    public function test_resetting_own_password_does_not_force_another_change(): void
    {
        $role = Role::where('name', 'super_admin')->first();
        $admin = User::factory()->create(['role_id' => $role->id, 'must_change_password' => false]);

        $this->actingAs($admin)->postJson("/api/v1/users/{$admin->id}/reset-password", [
            'password' => 'Str0ng#Pass1', 'password_confirmation' => 'Str0ng#Pass1',
        ])->assertOk();

        $this->assertFalse((bool) $admin->fresh()->must_change_password);
    }
}
