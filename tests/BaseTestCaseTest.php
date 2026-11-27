<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Nwidart\Modules\Contracts\RepositoryInterface;

class BaseTestCaseTest extends BaseTestCase
{
    private string $bin;

    /** @var array{0: string|false, 1: mixed, 2: mixed} */
    private array $path;

    protected function setUp(): void
    {
        parent::setUp();

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('The composer shim is a shell script.');
        }

        // A composer shim, first on the PATH, that records each call instead of running composer.
        $this->bin = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-modules-composer-shim-'.uniqid();
        mkdir($this->bin);
        file_put_contents($this->bin.'/composer', "#!/bin/sh\necho \"\$*\" >> \"\$(dirname \"\$0\")/calls\"\n");
        chmod($this->bin.'/composer', 0755);

        $this->path = [getenv('PATH'), $_SERVER['PATH'] ?? null, $_ENV['PATH'] ?? null];
        $path = $this->bin.PATH_SEPARATOR.$this->path[0];
        putenv("PATH={$path}");
        $_SERVER['PATH'] = $path;
        if (isset($_ENV['PATH'])) {
            $_ENV['PATH'] = $path;
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->bin)) {
            [$env, $server, $globalEnv] = $this->path;
            putenv($env === false ? 'PATH' : "PATH={$env}");
            $server === null ? $_SERVER = array_diff_key($_SERVER, ['PATH' => true]) : $_SERVER['PATH'] = $server;
            $globalEnv === null ? $_ENV = array_diff_key($_ENV, ['PATH' => true]) : $_ENV['PATH'] = $globalEnv;

            (new Filesystem)->deleteDirectory($this->bin);

            $repository = $this->app[RepositoryInterface::class];
            if ($repository->has('Blog')) {
                $repository->delete('Blog');
            }
        }

        parent::tearDown();
    }

    public function test_the_shim_runs_when_composer_is_called_for_real()
    {
        Process::run('composer --version');

        $this->assertSame("--version\n", $this->calls());
    }

    public function test_module_make_does_not_run_a_real_composer_dump_autoload()
    {
        $this->assertSame(0, $this->createModule());

        $this->assertSame('', $this->calls());
    }

    public function test_a_test_can_still_assert_that_the_dump_ran()
    {
        Process::fake();

        $this->createModule();

        Process::assertRan('composer dump-autoload');
        $this->assertSame('', $this->calls());
    }

    private function calls(): string
    {
        return is_file($this->bin.'/calls') ? (string) file_get_contents($this->bin.'/calls') : '';
    }
}
