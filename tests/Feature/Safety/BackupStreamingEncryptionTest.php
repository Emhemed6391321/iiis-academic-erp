<?php

namespace Tests\Feature\Safety;

use App\Services\EnterpriseBackupService;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Hotfix 2.5.10: backup encryption must stream in chunks (the archive now
 * includes the vault and exceeded PHP's 128MB memory limit), while producing
 * exactly the same bytes as the previous one-shot AES-256-CBC so existing
 * backups still decrypt.
 */
class BackupStreamingEncryptionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('app/backups/stream_test_' . uniqid());
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function encrypt(string $src, string $dst): void
    {
        $m = new ReflectionMethod(EnterpriseBackupService::class, 'encryptFile');
        $m->setAccessible(true);
        $m->invoke(null, $src, $dst);
    }

    private function key(): string
    {
        $m = new ReflectionMethod(EnterpriseBackupService::class, 'getEncryptionKey');
        $m->setAccessible(true);
        return hash('sha256', $m->invoke(null), true);
    }

    public static function sizes(): array
    {
        return [[0], [1], [15], [16], [17], [65535], [65536], [65537], [131072], [200003]];
    }

    /** @dataProvider sizes */
    public function test_streamed_output_matches_one_shot_cbc_and_round_trips(int $size): void
    {
        $plain = $size ? random_bytes($size) : '';
        File::put("{$this->dir}/in.bin", $plain);

        $this->encrypt("{$this->dir}/in.bin", "{$this->dir}/out.enc");
        $enc = File::get("{$this->dir}/out.enc");

        $iv = substr($enc, 0, 16);
        $expected = openssl_encrypt($plain, 'AES-256-CBC', $this->key(), OPENSSL_RAW_DATA, $iv);
        $this->assertSame(bin2hex($expected), bin2hex(substr($enc, 16)), 'Ciphertext must equal legacy one-shot output');

        EnterpriseBackupService::decryptFile("{$this->dir}/out.enc", "{$this->dir}/back.bin");
        $this->assertSame(hash('sha256', $plain), hash_file('sha256', "{$this->dir}/back.bin"));
    }

    public function test_legacy_one_shot_backup_still_decrypts(): void
    {
        $plain = random_bytes(100001);
        $iv = random_bytes(16);
        File::put("{$this->dir}/legacy.enc", $iv . openssl_encrypt($plain, 'AES-256-CBC', $this->key(), OPENSSL_RAW_DATA, $iv));

        EnterpriseBackupService::decryptFile("{$this->dir}/legacy.enc", "{$this->dir}/legacy.bin");
        $this->assertSame($plain, File::get("{$this->dir}/legacy.bin"));
    }

    public function test_large_file_does_not_load_into_memory(): void
    {
        $src = "{$this->dir}/big.bin";
        $h = fopen($src, 'wb');
        $block = random_bytes(1024 * 1024);
        for ($i = 0; $i < 24; $i++) {
            fwrite($h, $block);
        }
        fclose($h);
        unset($block);

        $before = memory_get_usage(true);
        memory_reset_peak_usage();
        $this->encrypt($src, "{$this->dir}/big.enc");
        EnterpriseBackupService::decryptFile("{$this->dir}/big.enc", "{$this->dir}/big.out");
        $grew = memory_get_peak_usage(true) - $before;

        $this->assertLessThan(8 * 1024 * 1024, $grew, 'Encrypt/decrypt of 24MB must stay well under 8MB extra memory');
        $this->assertSame(hash_file('sha256', $src), hash_file('sha256', "{$this->dir}/big.out"));
    }

    public function test_wrong_key_or_tampered_tail_fails_loudly(): void
    {
        File::put("{$this->dir}/in.bin", random_bytes(70000));
        $this->encrypt("{$this->dir}/in.bin", "{$this->dir}/out.enc");
        $bytes = File::get("{$this->dir}/out.enc");
        $bytes[strlen($bytes) - 1] = chr(ord($bytes[strlen($bytes) - 1]) ^ 0xFF);
        File::put("{$this->dir}/out.enc", $bytes);

        $this->expectException(\Exception::class);
        EnterpriseBackupService::decryptFile("{$this->dir}/out.enc", "{$this->dir}/back.bin");
    }
}
