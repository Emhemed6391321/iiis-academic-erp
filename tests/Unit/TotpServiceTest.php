<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TotpService;

class TotpServiceTest extends TestCase
{
    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    public function test_can_generate_valid_base32_secret()
    {
        $secret = $this->totp->generateSecret(32);
        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_can_generate_and_verify_correct_totp_code()
    {
        $secret = $this->totp->generateSecret();
        $code = $this->totp->getCode($secret);

        $this->assertEquals(6, strlen($code));
        $this->assertTrue($this->totp->verifyCode($secret, $code));
    }

    public function test_rejects_invalid_totp_code()
    {
        $secret = $this->totp->generateSecret();
        $this->assertFalse($this->totp->verifyCode($secret, '000000'));
        $this->assertFalse($this->totp->verifyCode($secret, 'abcdef'));
        $this->assertFalse($this->totp->verifyCode($secret, '123'));
    }

    public function test_tolerates_one_step_drift()
    {
        $secret = $this->totp->generateSecret();
        $pastCode = $this->totp->getCode($secret, time() - 25);
        $this->assertTrue($this->totp->verifyCode($secret, $pastCode, 1));
    }

    public function test_generates_correct_otpauth_url()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $url = $this->totp->getOtpAuthUrl('admin@iiis.sch.ly', $secret, 'IIIS Enterprise');

        $this->assertStringStartsWith('otpauth://totp/', $url);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $url);
        $this->assertStringContainsString('admin%40iiis.sch.ly', $url);
    }

    public function test_generates_recovery_codes()
    {
        $codes = $this->totp->generateRecoveryCodes(8);
        $this->assertCount(8, $codes);
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-F0-9]{4}-[A-F0-9]{4}$/', $code);
        }
    }
}
