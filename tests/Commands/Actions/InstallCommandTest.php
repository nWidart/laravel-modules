<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Process\Concerns\CreatesLocalGitRepository;
use Symfony\Component\Process\Process;

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

        // module:install updates only modules the repository finds, so scan the install dir.
        $this->app[RepositoryInterface::class]->addLocation(dirname($this->destination));

        $this->updateCommand = new class extends Command
        {
            protected $signature = 'module:update {module?*}';

            /** @var list<string> */
            public array $updated = [];

            public int $exitCode = self::SUCCESS;

            public function handle(): int
            {
                array_push($this->updated, ...(array) $this->argument('module'));

                return $this->exitCode;
            }
        };

        $this->app[Kernel::class]->registerCommand($this->updateCommand);
    }

    protected function tearDown(): void
    {
        $this->deleteTmpDir();
        @unlink(base_path('modules.json'));
        (new Filesystem)->deleteDirectory($this->app[RepositoryInterface::class]->getModulePath('Blog'));
        (new Filesystem)->deleteDirectory(base_path('modules/BlogModule'));
        (new Filesystem)->deleteDirectory($this->app[RepositoryInterface::class]->getModulePath('Shop'));

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
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl, 'path' => $this->destination],
            ],
        ]));

        $code = $this->artisan('module:install');

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
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_modules_json_installs_each_module_into_its_own_path()
    {
        $shopDestination = dirname($this->destination).DIRECTORY_SEPARATOR.'Shop';
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl, 'path' => $this->destination],
                ['name' => 'acme/shop', 'version' => 'develop', 'type' => $this->createBareRepository('Shop'), 'path' => $shopDestination],
            ],
        ]));

        $code = $this->artisan('module:install');

        $this->assertSame(0, $code, Artisan::output());
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertSame('develop', $this->currentBranch($shopDestination));
        $this->assertSame(['Blog', 'Shop'], $this->updateCommand->updated);
    }

    public function test_modules_json_installs_an_entry_without_path_into_the_modules_path()
    {
        $shopDestination = $this->app[RepositoryInterface::class]->getModulePath('Shop');
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl, 'path' => $this->destination],
                ['name' => 'acme/shop', 'version' => 'develop', 'type' => $this->createBareRepository('Shop')],
            ],
        ]));

        $code = $this->artisan('module:install');

        $this->assertSame(0, $code, Artisan::output());
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertDirectoryDoesNotExist(base_path('modules/Blog'));
        $this->assertFileExists($shopDestination.'module.json');
        $this->assertSame(['Blog', 'Shop'], $this->updateCommand->updated);
    }

    public function test_path_option_with_several_modules_json_entries_fails_without_installing()
    {
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl],
                ['name' => 'acme/shop', 'version' => 'develop', 'type' => $this->createBareRepository('Shop')],
            ],
        ]));

        $code = $this->artisan('module:install', ['--path' => $this->destination]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            'The --path option sets the folder of a single module, but modules.json lists 2 modules. Remove --path and set a "path" on each entry of modules.json instead.',
            Artisan::output()
        );
        $this->assertDirectoryDoesNotExist($this->destination);
        $this->assertDirectoryDoesNotExist($this->app[RepositoryInterface::class]->getModulePath('Blog'));
        $this->assertDirectoryDoesNotExist($this->app[RepositoryInterface::class]->getModulePath('Shop'));
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_the_entry_path_wins_over_the_path_option_for_a_single_modules_json_entry()
    {
        $optionDestination = $this->tmpDir.DIRECTORY_SEPARATOR.'option dir'.DIRECTORY_SEPARATOR.'Blog';
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl, 'path' => $this->destination],
            ],
        ]));

        $code = $this->artisan('module:install', ['--path' => $optionDestination]);

        $this->assertSame(0, $code, Artisan::output());
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertDirectoryDoesNotExist($optionDestination);
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_it_installs_the_master_branch_when_no_version_is_given()
    {
        $this->addBranchToBareRepository('master');

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            '--type' => $this->repoUrl,
            '--path' => $this->destination,
        ]);

        $this->assertSame(0, $code);
        $this->assertSame('master', $this->currentBranch($this->destination));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_it_installs_into_the_modules_path_when_no_path_is_given()
    {
        $destination = $this->app[RepositoryInterface::class]->getModulePath('Blog');
        $this->assertDirectoryDoesNotExist($destination);

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => $this->repoUrl,
        ]);

        $this->assertSame(0, $code);
        $this->assertFileExists($destination.'module.json');
        $this->assertSame('develop', $this->currentBranch($destination));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_repository_install_runs_and_installs_into_the_modules_path()
    {
        $this->addBranchToBareRepository('master');
        $repository = $this->app[RepositoryInterface::class];
        $destination = $repository->getModulePath('Blog');

        $process = $repository->install('acme/blog', 'master', $this->repoUrl);

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertFileExists($destination.'module.json');
        $this->assertSame('master', $this->currentBranch($destination));
    }

    public function test_repository_install_without_a_version_checks_out_master_via_git()
    {
        $this->addBranchToBareRepository('master');
        $repository = $this->app[RepositoryInterface::class];
        $destination = $repository->getModulePath('Blog');

        $process = $repository->install('acme/blog', type: $this->repoUrl);

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('master', $process->getEnv()['MODULE_BRANCH']);
        $this->assertSame('master', $this->currentBranch($destination));
    }

    public function test_repository_install_keeps_an_explicit_version()
    {
        $repository = $this->app[RepositoryInterface::class];

        $process = $repository->install('acme/blog', 'develop', $this->repoUrl);

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('develop', $this->currentBranch($repository->getModulePath('Blog')));
    }

    public function test_repository_install_subclasses_with_the_previous_signature_stay_compatible()
    {
        $repository = new class($this->app) extends LaravelFileRepository
        {
            public function install(string $name, string $version = 'dev-master', string $type = 'composer', bool $subtree = false): Process
            {
                return parent::install($name, $version, $type, $subtree);
            }
        };

        $this->assertInstanceOf(LaravelFileRepository::class, $repository);
    }

    public function test_it_updates_the_module_named_in_module_json_when_the_folder_name_differs()
    {
        $code = $this->artisan('module:install', [
            'name' => 'acme/blog-module',
            'version' => 'develop',
            '--type' => $this->repoUrl,
        ]);

        $this->assertSame(0, $code);
        $this->assertFileExists(base_path('modules/BlogModule/module.json'));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_a_module_installed_outside_the_scanned_paths_fails_with_a_clear_message()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'elsewhere'.DIRECTORY_SEPARATOR.'Blog';

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => $this->repoUrl,
            '--path' => $destination,
        ]);

        $this->assertSame(1, $code);
        $this->assertFileExists($destination.DIRECTORY_SEPARATOR.'module.json');
        $this->assertStringContainsString(
            'Module [Blog] was installed, but the modules repository cannot find it, so module:update was skipped.',
            Artisan::output()
        );
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_no_update_skips_the_module_lookup()
    {
        $destination = $this->tmpDir.DIRECTORY_SEPARATOR.'elsewhere'.DIRECTORY_SEPARATOR.'Blog';

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => $this->repoUrl,
            '--path' => $destination,
            '--no-update' => true,
        ]);

        $this->assertSame(0, $code);
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_a_failed_update_exits_with_failure()
    {
        $this->updateCommand->exitCode = Command::FAILURE;

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => $this->repoUrl,
            '--path' => $this->destination,
        ]);

        $this->assertSame(1, $code);
        $this->assertSame(['Blog'], $this->updateCommand->updated);
        $this->assertStringContainsString('Module [Blog] was installed, but module:update failed (exit code 1).', Artisan::output());
    }

    public function test_modules_json_reports_a_failed_update()
    {
        $this->updateCommand->exitCode = Command::FAILURE;
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl],
            ],
        ]));

        $code = $this->artisan('module:install', ['--path' => $this->destination]);

        $this->assertSame(1, $code);
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_modules_json_skips_an_entry_without_a_valid_name_and_installs_the_rest()
    {
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => [
                ['version' => 'develop', 'type' => $this->repoUrl],
                ['name' => 42, 'version' => 'develop', 'type' => $this->repoUrl],
                ['name' => 'acme/blog', 'version' => 'develop', 'type' => $this->repoUrl, 'path' => $this->destination],
            ],
        ]));

        $code = $this->artisan('module:install');

        $output = Artisan::output();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('Skipping entry #1 of the modules.json "require" list: "name" must be a package name such as "vendor/module".', $output);
        $this->assertStringContainsString('Skipping entry #2 of the modules.json "require" list: "name" must be a package name such as "vendor/module".', $output);
        $this->assertSame('develop', $this->currentBranch($this->destination));
        $this->assertSame(['Blog'], $this->updateCommand->updated);
    }

    public function test_modules_json_reports_a_require_keyed_by_package_name()
    {
        file_put_contents(base_path('modules.json'), json_encode([
            'require' => ['acme/blog' => 'dev-master', 'acme/shop' => 'dev-master'],
        ]));

        $code = $this->artisan('module:install');

        $output = Artisan::output();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('Skipping entry #1 of the modules.json "require" list: expected an object such as {"name": "vendor/module"}, got string.', $output);
        $this->assertStringContainsString('Skipping entry #2 of the modules.json "require" list: expected an object such as {"name": "vendor/module"}, got string.', $output);
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_modules_json_reports_a_require_that_is_not_a_list()
    {
        file_put_contents(base_path('modules.json'), json_encode(['require' => 'acme/blog']));

        $code = $this->artisan('module:install');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('The "require" key of modules.json must be a list of entries such as {"name": "vendor/module"}.', Artisan::output());
        $this->assertSame([], $this->updateCommand->updated);
    }

    public function test_tree_install_with_uncommitted_changes_fails_with_a_clear_message()
    {
        $project = $this->useGitProjectAsBasePath();
        file_put_contents($project.DIRECTORY_SEPARATOR.'composer.json', '{"name": "changed"}');

        $code = $this->artisan('module:install', [
            'name' => 'acme/blog',
            'version' => 'develop',
            '--type' => $this->repoUrl,
            '--path' => 'Modules/Blog',
            '--tree' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            'Failed to install module [acme/blog]: A subtree install (--tree) needs a clean working tree: commit or stash your changes first. Untracked files are fine.',
            Artisan::output()
        );
        $this->assertDirectoryDoesNotExist($project.'/Modules/Blog');
        $this->assertSame([], $this->updateCommand->updated);
    }
}
