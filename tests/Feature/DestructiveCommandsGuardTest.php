<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestructiveCommandsGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_branches_refused_in_production_without_flag(): void
    {
        Branch::create(['code' => 'X1', 'name' => 'فرع', 'city' => 'طرابلس', 'address' => 'x']);
        $this->app['env'] = 'production';

        $this->artisan('app:reset-branches', ['--force' => true])->assertExitCode(1);
        $this->assertSame(1, Branch::count());
    }

    public function test_prepare_launch_refused_in_production_when_not_interactive(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('app:prepare-production-launch', ['--force' => true, '--allow-production' => true, '--no-interaction' => true])
            ->assertExitCode(1);
    }

    public function test_prepare_launch_generates_random_admin_password_outside_production(): void
    {
        $this->artisan('app:prepare-production-launch', ['--force' => true])->assertExitCode(0);

        $admin = \App\Models\User::where('email', 'admin@iiis.sch.ly')->first();
        $this->assertNotNull($admin);
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('112200225124', $admin->password));
        $this->assertTrue((bool) $admin->must_change_password);
    }

    public function test_contract_wipe_migration_no_longer_deletes_data(): void
    {
        $this->assertStringNotContainsString(
            '->delete()',
            file_get_contents(database_path('migrations/2026_10_03_203000_wipe_all_branch_contracts_and_installments.php'))
        );
    }
}
