<?php

namespace Tests\Feature;

use App\Services\EnterpriseBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackupDatabaseSnapshotTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/iiis_backup_' . uniqid();
        File::makeDirectory($this->dir, 0755, true);
    }

    protected function tearDown(): void
    {
        DB::purge('snapshot_src');
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function dump(): array
    {
        $dbFile = $this->dir . '/live.sqlite';
        touch($dbFile);
        config([
            'database.connections.snapshot_src' => ['driver' => 'sqlite', 'database' => $dbFile, 'prefix' => '', 'foreign_key_constraints' => true],
            'database.default' => 'snapshot_src',
        ]);
        DB::purge('snapshot_src');

        Schema::create('widgets', fn ($t) => $t->id());
        DB::table('widgets')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);

        $m = new \ReflectionMethod(EnterpriseBackupService::class, 'dumpDatabase');
        $m->setAccessible(true);

        return $m->invoke(null, $this->dir, 'ts');
    }

    public function test_sqlite_backup_is_a_consistent_snapshot_with_the_live_data(): void
    {
        $info = $this->dump();

        $this->assertSame('sqlite', $info['driver']);
        $snapshot = $this->dir . '/database/' . $info['file_name'];
        $this->assertFileExists($snapshot);
        $this->assertSame(hash_file('sha256', $snapshot), $info['sha256']);

        $pdo = new \PDO('sqlite:' . $snapshot);
        $this->assertSame(3, (int) $pdo->query('select count(*) from widgets')->fetchColumn());
        $this->assertSame('ok', $pdo->query('pragma integrity_check')->fetchColumn());
    }

    public function test_mysql_backup_fails_loudly_when_the_dump_tool_is_missing(): void
    {
        config([
            'database.connections.mysql_x' => [
                'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'database' => 'x', 'username' => 'u', 'password' => 'p',
                'dump' => ['dump_binary_path' => $this->dir . '/no-such-dir'],
            ],
            'database.default' => 'mysql_x',
        ]);

        $m = new \ReflectionMethod(EnterpriseBackupService::class, 'dumpDatabase');
        $m->setAccessible(true);

        $this->expectException(\Exception::class);
        $m->invoke(null, $this->dir, 'ts');
    }
}
