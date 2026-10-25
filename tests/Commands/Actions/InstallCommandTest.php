<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Process\Concerns\CreatesLocalGitRepository;

class InstallCommandTest extends BaseTestCase
{
    use CreatesLocalGitRepository;

    private string $repoUrl;

    private string $destination;

    /**
     * Records the modules passed to module:update instead of running composer.
     */
    private Command $updateCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTmpDir();
        $this->repoUrl = $this->createBareRepository();
        $this->destination = $this->tmpDir.DIRECTORY_SEPARATOR.'install dir'.DIRECTORY_SEPARATOR.'Blog';

        $this->updateCommand = new class extends Command
        {
            protected $signature = 'module:update {module?*}';

            /** @var list<string> */
            public array $updated = [];

            public function handle(): int
            {
                array_push($this->updated, ...(array) $this->argument('module'));

                return self::SUCCESS;
            }
        };

        $this->app[Kernel::class]->registerCommand($this->updateCommand);
    }

    protected function tearDown(): void
    {
        $this->deleteTmpDir();
        @unlink(base_path('modules.json'));

        parent::tearDown();
    }

    public function test_it_installs_a_module_and_updates_it()
    {
        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => $this->repoUrl,
            '--path' => $this->destination,
        ]);

        $this->assertSame(0, $code);
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_a_failed_install_exits_with_failure_and_skips_the_update()
    {
        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => 'file://'.$this->tmpDir.'/missing.git',
            '--path' => $this->destination,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Failed to install module [acme/blog]', Artisan::output());
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_an_injected_version_fails_without_running_the_command()
    {
        $marker = $this->tmpDir.DIRECTORY_SEPARATOR.'pwned';

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => "develop;touch {$marker}",
            '--type' => $this->repoUrl,
            '--path' => $this->destination,
        ]);

        $this->assertSame(1, $code);
        $this->assertFileDoesNotExist($marker);
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_an_invalid_repo_type_exits_with_failure()
    {
        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => 'not-a-repo',
            '--path' => $this->destination,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Failed to install module [acme/blog]', Artisan::output());
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_modules_json_installs_the_remaining_modules_and_reports_failure()
    {
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/broken', 'version' => 'develop', 'type' => 'file://'.$this->tmpDir.'/missing.git'],
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl],
            ],
        ]));

        $code = $this->artisan('module:install', ['--path' => $this->destination]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Failed to install module [acme/broken]', Artisan::output());
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_modules_json_succeeds_when_every_module_installs()
    {
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl],
            ],
        ]));

        $code = $this->artisan('module:install', ['--path' => $this->destination]);

        $this->assertSame(0, $code);
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }
}
