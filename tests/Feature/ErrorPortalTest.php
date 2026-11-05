<?php

namespace Tests\Feature;

use App\Models\SystemErrorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPortalTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = 'ops-test-path';
    private const PASSWORD = 'correct-horse-battery';

    public function createApplication()
    {
        // المسارات تُسجَّل عند الإقلاع، لذا تُضبط البيئة قبله
        putenv('ERROR_PORTAL_PATH=' . self::PATH);
        putenv('ERROR_PORTAL_PASSWORD_HASH=' . password_hash(self::PASSWORD, PASSWORD_BCRYPT));

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        putenv('ERROR_PORTAL_PATH');
        putenv('ERROR_PORTAL_PASSWORD_HASH');
        parent::tearDown();
    }

    private function makeLog(): SystemErrorLog
    {
        return SystemErrorLog::create([
            'error_id' => 'ERR-TEST-1', 'error_hash' => md5('x'), 'error_type' => 'ERROR',
            'severity' => 'HIGH', 'message' => 'Boom happened', 'status' => 'NEW',
            'occurrences_count' => 3, 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
    }

    private function unlock(): void
    {
        $this->post('/' . self::PATH . '/login', ['password' => self::PASSWORD])->assertRedirect();
    }

    public function test_pages_redirect_to_login_when_locked(): void
    {
        $this->get('/' . self::PATH)->assertRedirect('/' . self::PATH . '/login');
        $this->get('/' . self::PATH . '/logs/1')->assertRedirect('/' . self::PATH . '/login');
    }

    public function test_wrong_password_is_rejected_and_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/' . self::PATH . '/login', ['password' => 'wrong'])->assertSessionHasErrors('password');
        }
        // بعد 5 محاولات خاطئة تُرفض حتى الكلمة الصحيحة
        $this->post('/' . self::PATH . '/login', ['password' => self::PASSWORD]);
        $this->get('/' . self::PATH)->assertRedirect('/' . self::PATH . '/login');
    }

    public function test_correct_password_opens_list_detail_and_prompt(): void
    {
        $log = $this->makeLog();
        $this->unlock();

        $this->get('/' . self::PATH)->assertOk()->assertSee('ERR-TEST-1');
        $this->get('/' . self::PATH . '/logs/' . $log->id)
            ->assertOk()
            ->assertSee('Boom happened')
            ->assertSee('برومبت الإصلاح الذكي')
            ->assertHeader('X-Robots-Tag');
    }

    public function test_status_can_be_updated(): void
    {
        $log = $this->makeLog();
        $this->unlock();

        $this->post('/' . self::PATH . '/logs/' . $log->id . '/status', ['status' => 'RESOLVED', 'notes' => 'fixed'])
            ->assertRedirect();
        $this->assertSame('RESOLVED', $log->fresh()->status);
    }

    public function test_session_expires_after_idle(): void
    {
        $this->unlock();
        $this->withSession(['error_portal_unlocked_at' => time() - 3600])
            ->get('/' . self::PATH)->assertRedirect('/' . self::PATH . '/login');
    }

    public function test_application_user_session_does_not_unlock_portal(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())
            ->get('/' . self::PATH)->assertRedirect('/' . self::PATH . '/login');
    }
}
