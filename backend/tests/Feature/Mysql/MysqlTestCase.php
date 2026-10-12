<?php

namespace Tests\Feature\Mysql;

use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class MysqlTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $database = env('MYSQL_TEST_DATABASE');

        if (! $database) {
            $this->markTestSkipped('Set MYSQL_TEST_DATABASE to run MySQL concurrency tests.');
        }

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('The pcntl extension is required to fork parallel workers.');
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => $database,
        ]);

        DB::purge('mysql');
    }

    /**
     * @template TResult
     *
     * @param  Closure(int): TResult  $worker
     * @return list<TResult>
     */
    protected function runInParallel(int $workers, Closure $worker): array
    {
        $resultDirectory = sys_get_temp_dir().'/support-desk-parallel-'.uniqid();
        mkdir($resultDirectory);

        DB::disconnect();

        $pids = [];

        for ($index = 0; $index < $workers; $index++) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                $this->runWorker($worker, $index, "{$resultDirectory}/{$index}");
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        DB::reconnect();

        return $this->collectResults($resultDirectory, $workers);
    }

    private function runWorker(Closure $worker, int $index, string $resultFile): never
    {
        DB::reconnect();

        try {
            $outcome = ['result' => $worker($index)];
        } catch (Throwable $exception) {
            $outcome = ['error' => $exception->getMessage()];
        }

        file_put_contents($resultFile, serialize($outcome));
        posix_kill(posix_getpid(), SIGKILL);
    }

    /** @return list<mixed> */
    private function collectResults(string $resultDirectory, int $workers): array
    {
        $results = [];

        for ($index = 0; $index < $workers; $index++) {
            $file = "{$resultDirectory}/{$index}";
            $outcome = file_exists($file) ? unserialize(file_get_contents($file)) : ['error' => 'worker produced no result'];

            if (array_key_exists('error', $outcome)) {
                $this->fail("Worker {$index} failed: {$outcome['error']}");
            }

            $results[] = $outcome['result'];
            unlink($file);
        }

        rmdir($resultDirectory);

        return $results;
    }
}
