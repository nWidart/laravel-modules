<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Traits\MemoryConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleTestCommandTest extends BaseTestCase
{
    private const JUNIT = '{junit}';

    private RepositoryInterface $repository;

    /**
     * The command lines the fake ran, with the JUnit path replaced by self::JUNIT.
     *
     * @var list<list<string>>
     */
    private array $ran = [];

    /**
     * The JUnit paths the fake was given.
     *
     * @var list<string>
     */
    private array $junitPaths = [];

    /**
     * Temporary folders to remove in tearDown.
     *
     * @var list<string>
     */
    private array $temporary = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        $this->repository->delete('Shop');

        foreach ($this->temporary as $folder) {
            File::deleteDirectory($folder);
        }

        parent::tearDown();
    }

    public function test_it_runs_phpunit_when_there_is_no_artisan_test_and_no_pest()
    {
        $this->fakeRunner(['Blog' => 0]);

        $code = $this->artisan('module:test', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertSame([
            [base_path('vendor/bin/phpunit'), '--log-junit', self::JUNIT, $this->moduleTestsFolder('Blog')],
        ], $this->ran);
        Process::assertRan(fn (PendingProcess $process) => $process->path === base_path() && $process->timeout === null);
    }

    public function test_it_runs_pest_when_installed()
    {
        $base = $this->temporaryFolder();
        File::ensureDirectoryExists($base.'/vendor/bin');
        File::put($base.'/vendor/bin/pest', '');
        $this->app->setBasePath($base);
        $this->fakeRunner(['Blog' => 0]);

        $code = $this->artisan('module:test', ['module' => ['Blog'], '--filter' => 'creates_a_post', '--parallel' => true]);

        $this->assertSame(0, $code);
        $this->assertSame([
            [$base.'/vendor/bin/pest', '--parallel', '--filter=creates_a_post', '--log-junit', self::JUNIT, $this->moduleTestsFolder('Blog')],
        ], $this->ran);
        Process::assertRan(fn (PendingProcess $process) => $process->path === $base);
    }

    public function test_it_runs_artisan_test_when_the_app_has_it()
    {
        $this->registerArtisanTestCommand();
        $this->fakeRunner(['Blog' => 0]);

        $code = $this->artisan('module:test', ['module' => ['Blog'], '--filter' => 'PostTest', '--parallel' => true]);

        $this->assertSame(0, $code);
        $this->assertSame([
            [PHP_BINARY, 'artisan', 'test', '--parallel', '--filter=PostTest', '--log-junit', self::JUNIT, $this->moduleTestsFolder('Blog')],
        ], $this->ran);
    }

    public function test_artisan_test_gets_its_own_options_before_the_folder()
    {
        // artisan test (Collision) passes everything after the first argument to PHPUnit,
        // which rejects --parallel, so the folder must come last.
        $this->registerArtisanTestCommand();
        $this->fakeRunner(['Blog' => 0]);

        $this->artisan('module:test', ['module' => ['Blog'], '--parallel' => true]);

        $command = $this->ran[0];
        $this->assertSame($this->moduleTestsFolder('Blog'), $command[array_key_last($command)]);
        $this->assertLessThan(array_search($this->moduleTestsFolder('Blog'), $command, true), array_search('--parallel', $command, true));
        $this->assertSame([PHP_BINARY, 'artisan', 'test', '--parallel'], array_slice($command, 0, 4));
    }

    public function test_parallel_with_plain_phpunit_fails_without_running_anything()
    {
        Process::fake();

        $code = $this->artisan('module:test', ['module' => ['Blog'], '--parallel' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--parallel needs artisan test or Pest.', Artisan::output());
        Process::assertNothingRan();
    }

    public function test_parallel_with_plain_phpunit_fails_in_json_mode()
    {
        Process::fake();
        $output = new MemoryConsoleOutput;

        $code = $this->runCommand(['module' => ['Blog'], '--parallel' => true, '--json' => true], $output);

        $this->assertSame(1, $code);
        $this->assertSame('{"modules":[],"passed":0,"failed":0,"skipped":0,"exit_code":1}'."\n", $output->stdout());
        $this->assertStringContainsString('--parallel needs artisan test or Pest.', $output->stderr());
        Process::assertNothingRan();
    }

    public function test_it_returns_the_exit_code_of_the_runner()
    {
        foreach ([0, 1, 2] as $exitCode) {
            $this->ran = [];
            $this->fakeRunner(['Blog' => $exitCode]);

            $this->assertSame($exitCode, $this->artisan('module:test', ['module' => ['Blog']]), "exit code {$exitCode}");
        }
    }

    public function test_it_returns_the_first_non_zero_exit_code_and_runs_every_module()
    {
        $this->fakeRunner(['Blog' => 2, 'Shop' => 1]);
        $this->assertSame(2, $this->artisan('module:test', ['module' => ['Blog', 'Shop']]));
        $this->assertCount(2, $this->ran);

        $this->ran = [];
        $this->fakeRunner(['Blog' => 0, 'Shop' => 2]);
        $this->assertSame(2, $this->artisan('module:test', ['module' => ['Blog', 'Shop']]));
        $this->assertCount(2, $this->ran);
    }

    public function test_it_runs_every_module_with_all_including_disabled_ones()
    {
        $this->repository->find('Shop')->disable();
        $this->fakeRunner(['Blog' => 0, 'Shop' => 0]);

        $code = $this->artisan('module:test', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertEqualsCanonicalizing(
            [$this->moduleTestsFolder('Blog'), $this->moduleTestsFolder('Shop')],
            $this->ranFolders(),
        );
    }

    public function test_it_skips_a_module_without_tests_folder()
    {
        File::deleteDirectory($this->moduleTestsFolder('Shop'));
        $this->fakeRunner(['Blog' => 0]);
        $output = new MemoryConsoleOutput;

        $code = $this->runCommand(['module' => ['Shop', 'Blog']], $output);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Module [Shop] has no tests folder.', $output->stdout());
        $this->assertSame([$this->moduleTestsFolder('Blog')], $this->ranFolders());
    }

    public function test_a_skipped_module_does_not_fail_the_run()
    {
        File::deleteDirectory($this->moduleTestsFolder('Shop'));
        Process::fake();

        $this->assertSame(0, $this->artisan('module:test', ['module' => ['Shop']]));
        Process::assertNothingRan();
    }

    public function test_text_mode_streams_the_runner_output_to_stdout()
    {
        $this->fakeRunner(['Blog' => 0], output: "PHPUnit 12 by Sebastian Bergmann\n<ok> 3 tests\n");
        $output = new MemoryConsoleOutput;

        $this->runCommand(['module' => ['Blog']], $output);

        $this->assertStringContainsString("PHPUnit 12 by Sebastian Bergmann\n<ok> 3 tests\n", $output->stdout());
        $this->assertSame('', $output->stderr());
    }

    public function test_json_mode_writes_one_line_with_counts_and_totals()
    {
        File::deleteDirectory($this->moduleTestsFolder('Shop'));
        $this->fakeRunner(['Blog' => 1], junit: ['tests' => 10, 'failures' => 2, 'errors' => 1, 'skipped' => 3]);

        $code = $this->artisan('module:test', ['module' => ['Blog', 'Shop'], '--json' => true]);

        $this->assertSame(1, $code);
        $this->assertSame(
            '{"modules":['
            .'{"name":"Blog","ran":true,"passed":4,"failed":3,"skipped":3,"exit_code":1},'
            .'{"name":"Shop","ran":false,"passed":null,"failed":null,"skipped":null,"exit_code":null}'
            .'],"passed":4,"failed":3,"skipped":3,"exit_code":1}'."\n",
            Artisan::output(),
        );
    }

    public function test_json_totals_sum_the_modules()
    {
        $this->fakeRunner(['Blog' => 0, 'Shop' => 2], junit: ['tests' => 5, 'failures' => 1, 'errors' => 0, 'skipped' => 1]);

        $this->artisan('module:test', ['module' => ['Blog', 'Shop'], '--json' => true]);

        $document = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['passed' => 6, 'failed' => 2, 'skipped' => 2, 'exit_code' => 2], array_diff_key($document, ['modules' => true]));
        $this->assertSame([0, 2], array_column($document['modules'], 'exit_code'));
    }

    public function test_counts_are_null_when_the_runner_writes_no_junit_report()
    {
        $this->fakeRunner(['Blog' => 255], junit: null);

        $code = $this->artisan('module:test', ['module' => ['Blog'], '--json' => true]);

        $this->assertSame(255, $code);
        $this->assertSame(
            '{"modules":[{"name":"Blog","ran":true,"passed":null,"failed":null,"skipped":null,"exit_code":255}],"passed":0,"failed":0,"skipped":0,"exit_code":255}'."\n",
            Artisan::output(),
        );
    }

    public function test_counts_are_null_when_the_junit_report_cannot_be_parsed()
    {
        $this->fakeRunner(['Blog' => 2], junit: '<testsuites><testsuite tests="3"');

        $this->artisan('module:test', ['module' => ['Blog'], '--json' => true]);

        $document = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertNull($document['modules'][0]['passed']);
        $this->assertSame(2, $document['exit_code']);
    }

    public function test_counts_are_read_from_a_root_with_attributes()
    {
        $this->fakeRunner(['Blog' => 0], junit: '<?xml version="1.0"?><testsuites tests="7" failures="0" errors="0" skipped="2"><testsuite name="a" tests="7" failures="0" errors="0" skipped="2"/></testsuites>');

        $this->artisan('module:test', ['module' => ['Blog'], '--json' => true]);

        $document = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['passed' => 5, 'failed' => 0, 'skipped' => 2], array_intersect_key($document['modules'][0], array_flip(['passed', 'failed', 'skipped'])));
    }

    public function test_the_junit_report_is_deleted()
    {
        $this->fakeRunner(['Blog' => 0]);

        $this->artisan('module:test', ['module' => ['Blog']]);

        $this->assertCount(1, $this->junitPaths);
        $this->assertStringStartsWith(sys_get_temp_dir(), $this->junitPaths[0]);
        $this->assertFileDoesNotExist($this->junitPaths[0]);
    }

    public function test_json_mode_sends_the_runner_output_and_warnings_to_stderr()
    {
        File::deleteDirectory($this->moduleTestsFolder('Shop'));
        $this->fakeRunner(['Blog' => 0], output: "Runner says hello\n");
        $output = new MemoryConsoleOutput;

        $code = $this->runCommand(['module' => ['Blog', 'Shop'], '--json' => true], $output);

        $this->assertSame(0, $code);
        $this->assertCount(1, explode("\n", trim($output->stdout())));
        $this->assertStringStartsWith('{"modules":[{"name":"Blog","ran":true,"passed":3', $output->stdout());
        $this->assertStringNotContainsString('Runner says hello', $output->stdout());
        $this->assertStringContainsString('Runner says hello', $output->stderr());
        $this->assertStringContainsString('Module [Shop] has no tests folder.', $output->stderr());
    }

    public function test_decorated_json_mode_has_no_ansi_codes_on_stdout()
    {
        File::deleteDirectory($this->moduleTestsFolder('Shop'));
        $this->fakeRunner(['Blog' => 0], output: "Runner output\n");
        $output = new MemoryConsoleOutput(decorated: true);

        $this->runCommand(['module' => ['Blog', 'Shop'], '--json' => true, '--ansi' => true], $output);

        $this->assertStringNotContainsString("\e[", $output->stdout());
        $this->assertStringStartsWith('{"modules":', $output->stdout());
        $this->assertStringContainsString("\e[", $output->stderr());
    }

    public function test_it_unsets_the_variables_of_the_env_file_for_the_runner()
    {
        $folder = $this->temporaryFolder();
        File::put($folder.'/.env', "MODULE_TEST_FROM_DOTENV=local\nAPP_ENV=local\n");
        $this->app->useEnvironmentPath($folder);
        $this->fakeRunner(['Blog' => 0]);

        $this->artisan('module:test', ['module' => ['Blog']]);

        Process::assertRan(fn (PendingProcess $process) => $process->environment === ['MODULE_TEST_FROM_DOTENV' => false, 'APP_ENV' => false]);
    }

    public function test_it_keeps_the_environment_without_env_file()
    {
        $this->app->useEnvironmentPath($this->temporaryFolder());
        $this->fakeRunner(['Blog' => 0]);

        $this->artisan('module:test', ['module' => ['Blog']]);

        Process::assertRan(fn (PendingProcess $process) => $process->environment === []);
    }

    /**
     * Fake the test runner: each module's run writes a JUnit report and exits with the given code.
     *
     * @param  array<string, int>  $exitCodes  The exit code per module name.
     * @param  array<string, int>|string|null  $junit  The testsuite counts, raw XML, or null for no report.
     */
    private function fakeRunner(array $exitCodes, array|string|null $junit = ['tests' => 3, 'failures' => 0, 'errors' => 0, 'skipped' => 0], string $output = ''): void
    {
        Process::fake(function (PendingProcess $process) use ($exitCodes, $junit, $output) {
            $command = $process->command;
            $index = array_search('--log-junit', $command, true);
            $path = $command[$index + 1];
            $command[$index + 1] = self::JUNIT;

            $this->ran[] = $command;
            $this->junitPaths[] = $path;

            if (is_array($junit)) {
                $attributes = implode(' ', array_map(fn ($key, $value) => "{$key}=\"{$value}\"", array_keys($junit), $junit));
                file_put_contents($path, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<testsuites>\n  <testsuite name=\"tests\" {$attributes} time=\"0.1\"/>\n</testsuites>\n");
            } elseif (is_string($junit)) {
                file_put_contents($path, $junit);
            }

            $module = collect(array_keys($exitCodes))->first(fn ($name) => in_array($this->moduleTestsFolder($name), $command, true));

            return Process::result(output: $output, exitCode: $exitCodes[$module] ?? 0);
        });
    }

    /**
     * The tests folder of each command line the fake ran: the last argument.
     *
     * @return list<string>
     */
    private function ranFolders(): array
    {
        return array_map(fn (array $command) => $command[array_key_last($command)], $this->ran);
    }

    private function registerArtisanTestCommand(): void
    {
        $this->app[Kernel::class]->registerCommand(new ClosureCommand('test', fn () => 0));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function runCommand(array $parameters, OutputInterface $output): int
    {
        $command = $this->app[Kernel::class]->all()['module:test'];

        $input = new ArrayInput($parameters);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }

    private function moduleTestsFolder(string $module): string
    {
        return $this->repository->find($module)->getPath().DIRECTORY_SEPARATOR.'tests';
    }

    private function temporaryFolder(): string
    {
        $folder = sys_get_temp_dir().'/module-test-command-'.uniqid();
        File::ensureDirectoryExists($folder);

        return $this->temporary[] = $folder;
    }
}
