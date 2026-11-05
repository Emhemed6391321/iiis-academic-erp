<?php

namespace Tests\Feature;

use Tests\TestCase;

class SqliteTransactionModeTest extends TestCase
{
    public function test_sqlite_transactions_take_the_write_lock_up_front(): void
    {
        // ERR-20261104-35A49: DEFERRED كان يُفشل ترقية القراءة إلى كتابة فوراً بـ "database is locked"
        $this->assertSame('IMMEDIATE', config('database.connections.sqlite.transaction_mode'));
        $this->assertGreaterThanOrEqual(10000, (int) config('database.connections.sqlite.busy_timeout'));
    }

    public function test_the_failure_is_real_with_deferred_and_gone_with_immediate(): void
    {
        $file = sys_get_temp_dir() . '/lockcheck_' . uniqid() . '.sqlite';
        $open = function () use ($file) {
            $p = new \PDO("sqlite:$file");
            $p->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $p->exec('pragma journal_mode=WAL; pragma busy_timeout=3000');
            return $p;
        };
        $open()->exec('create table t(id integer primary key, v int)');

        try {
            // DEFERRED: قراءة ثم كتابة طلب آخر ثم ترقية -> فشل فوري
            $a = $open(); $b = $open();
            $a->exec('BEGIN DEFERRED');
            $a->query('select max(id) from t')->fetchAll();
            $b->exec('insert into t(v) values (1)');
            $failed = false;
            try { $a->exec('insert into t(v) values (2)'); } catch (\Throwable $e) { $failed = true; }
            $this->assertTrue($failed, 'DEFERRED should fail to upgrade after a concurrent commit');
            try { $a->exec('ROLLBACK'); } catch (\Throwable $e) {}

            // IMMEDIATE: يأخذ القفل أولاً فتنجح القراءة ثم الكتابة بلا تعارض
            $a = $open();
            $a->exec('BEGIN IMMEDIATE');
            $a->query('select max(id) from t')->fetchAll();
            $a->exec('insert into t(v) values (3)');
            $a->exec('COMMIT');
            $this->assertSame(2, (int) $open()->query('select count(*) from t')->fetchColumn());
        } finally {
            foreach (['', '-wal', '-shm'] as $suffix) { @unlink($file . $suffix); }
        }
    }
}
