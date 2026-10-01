<?php

namespace App\Services;

/**
 * Enterprise TOTP (RFC 6238 / RFC 4226) Service.
 * 
 * Provides cryptographically secure Time-based One-Time Passwords without external dependencies.
 * Compatible with Google Authenticator, Microsoft Authenticator, Authy, and hardware tokens.
 */
class TotpService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private int $timeStep = 30;
    private int $digits = 6;

    /**
     * Generate a random Base32 secret key (160 bits / 32 characters).
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        $max = strlen(self::BASE32_CHARS) - 1;

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $max)];
        }

        return $secret;
    }

    /**
     * Generate TOTP code for a secret at a specific timestamp.
     */
    public function getCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeCounter = (int) floor($timestamp / $this->timeStep);

        // Pack time counter into 8-byte big-endian binary string
        $binaryTime = pack('N*', 0) . pack('N*', $timeCounter);

        // Decode Base32 secret
        $binaryKey = $this->base32Decode($secret);

        // Calculate HMAC-SHA1
        $hash = hash_hmac('sha1', $binaryTime, $binaryKey, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord(substr($hash, -1)) & 0x0F;
        $unpacked = unpack('N', substr($hash, $offset, 4));
        $binaryCode = $unpacked[1] & 0x7FFFFFFF;

        $mod = 10 ** $this->digits;
        $otp = $binaryCode % $mod;

        return str_pad((string) $otp, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted TOTP code with time-step drift tolerance.
     * 
     * @param string $secret Base32 secret key
     * @param string $code 6-digit user input
     * @param int $discrepancy Allowed window drift (1 = ±30 seconds)
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $cleanCode = preg_replace('/\s+/', '', trim($code));
        if (strlen($cleanCode) !== $this->digits || !ctype_digit($cleanCode)) {
            return false;
        }

        $currentTime = time();

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $checkTime = $currentTime + ($i * $this->timeStep);
            if (hash_equals($this->getCode($secret, $checkTime), $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate standard otpauth URL for QR codes.
     */
    public function getOtpAuthUrl(string $accountEmail, string $secret, string $issuer = 'IIIS Enterprise ERP'): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountEmail);
        $encodedIssuer = rawurlencode($issuer);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits={$this->digits}&period={$this->timeStep}";
    }

    /**
     * Generate emergency one-time recovery codes (e.g. 8 codes formatted as XXXX-XXXX).
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(bin2hex(random_bytes(2)));
            $part2 = strtoupper(bin2hex(random_bytes(2)));
            $codes[] = "{$part1}-{$part2}";
        }

        return $codes;
    }

    /**
     * Decode a Base32 string to binary.
     */
    private function base32Decode(string $secret): string
    {
        $cleanSecret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($cleanSecret); $i++) {
            $val = strpos(self::BASE32_CHARS, $cleanSecret[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binary;
    }
}
