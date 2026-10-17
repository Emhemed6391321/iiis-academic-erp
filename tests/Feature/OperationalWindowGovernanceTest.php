<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\OperationalWindow;
use App\Models\OperationalWindowException;
use App\Models\StudyYear;
use App\Models\User;
use Carbon\Carbon;

class OperationalWindowGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        // Seeded accounts require a password change, which the API now enforces.
        User::query()->update(['must_change_password' => false]);
    }

    private function registrar(): User
    {
        return User::where('email', 'registrar.tip@iiis.sch.ly')->first();
    }

    /**
     * Posts a student registration as the Tripoli registrar. The window middleware runs
     * before the controller, so the body only needs to be present, not valid.
     */
    private function attemptRegistration(User $registrar)
    {
        return $this->actingAs($registrar)->postJson('/api/v1/students', [
            'branch_id' => $registrar->branch_id,
        ]);
    }

    private function registrationWindow(): OperationalWindow
    {
        return OperationalWindow::where('window_type', 'REGISTRATION')->firstOrFail();
    }

    public function test_missing_window_is_rejected_and_never_auto_created(): void
    {
        $this->registrationWindow()->delete();

        $response = $this->attemptRegistration($this->registrar());

        $response->assertStatus(403);
        $response->assertJsonPath('window_status', 'NOT_CONFIGURED');
        $this->assertSame(0, OperationalWindow::where('window_type', 'REGISTRATION')->count());
    }

    public function test_disabled_window_stays_closed_and_is_not_replaced(): void
    {
        $window = $this->registrationWindow();
        $window->update(['is_active' => false]);

        $response = $this->attemptRegistration($this->registrar());

        $response->assertStatus(403);
        $response->assertJsonPath('window_status', 'DISABLED');
        $this->assertFalse($window->fresh()->is_active);
        $this->assertSame(1, OperationalWindow::where('window_type', 'REGISTRATION')->count());
    }

    public function test_window_that_has_not_started_is_rejected(): void
    {
        $this->registrationWindow()->update([
            'start_at' => Carbon::now()->addDays(3),
            'end_at' => Carbon::now()->addDays(30),
        ]);

        $response = $this->attemptRegistration($this->registrar());

        $response->assertStatus(403);
        $response->assertJsonPath('window_status', 'NOT_STARTED');
    }

    public function test_approved_extension_keeps_only_the_extended_branch_open(): void
    {
        $window = $this->registrationWindow();
        $window->update([
            'start_at' => Carbon::now()->subDays(30),
            'end_at' => Carbon::now()->subDays(2),
        ]);

        $registrar = $this->registrar();
        OperationalWindowException::create([
            'window_id' => $window->id,
            'branch_id' => $registrar->branch_id,
            'granted_by' => $registrar->id,
            'extended_until' => Carbon::now()->addDays(5),
            'reason' => 'تمديد اختباري',
        ]);

        $response = $this->attemptRegistration($registrar);

        $this->assertNull($response->json('window_status'));
        $this->assertNotSame(403, $response->getStatusCode(), 'The extended branch must pass the window check.');
    }

    public function test_provisioning_creates_only_missing_windows_as_disabled(): void
    {
        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $existingRegistration = $this->registrationWindow()->fresh();
        OperationalWindow::where('window_type', 'S2_FINAL')->delete();

        $created = OperationalWindow::provisionMissingForYear($year->id);

        $this->assertSame(['S2_FINAL'], $created);
        $this->assertFalse(OperationalWindow::where('window_type', 'S2_FINAL')->firstOrFail()->is_active);
        $this->assertSame(
            $existingRegistration->title,
            $this->registrationWindow()->fresh()->title,
            'Existing windows must not be modified.'
        );
        $this->assertSame([], OperationalWindow::provisionMissingForYear($year->id), 'Provisioning is idempotent.');
    }
}
