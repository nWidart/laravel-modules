<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Commands\Make\Concerns\InspectsGeneratorRuns;
use Nwidart\Modules\Tests\Traits\MemoryConsoleOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;

class GeneratorJsonResultTest extends BaseTestCase
{
    use InspectsGeneratorRuns;

    /**
     * A folder made read-only by a test, made writable again in tearDown().
     */
    private ?string $readOnly = null;

    protected function tearDown(): void
    {
        if ($this->readOnly !== null) {
            chmod($this->readOnly, 0755);
        }

        foreach (['Blog', 'Shop'] as $name) {
            if ($this->app[RepositoryInterface::class]->find($name) !== null) {
                $this->app[RepositoryInterface::class]->delete($name);
            }
        }

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: array<string, bool>}>
     */
    public static function moduleTypes(): array
    {
        return [
            'web' => [[]],
            'plain' => [['--plain' => true]],
        ];
    }

    /**
     * @param  array<string, bool>  $options
     */
    #[DataProvider('moduleTypes')]
    public function test_module_make_json_lists_the_files_it_created(array $options)
    {
        $before = $this->listing(base_path('modules'));

        [$code, $document] = $this->runJson('module:make', ['name' => ['Blog'], '--json' => true] + $options);

        $this->assertSame(0, $code);
        $this->assertSame($this->textExitCode('module:make', ['name' => ['Shop']] + $options), $code);
        $this->assertSame('module:make', $document['command']);
        $this->assertFalse($document['dry_run']);
        $this->assertSame([], $document['errors']);

        foreach ($document['files'] as $file) {
            $this->assertSame(['path' => $file['path'], 'exists' => false, 'action' => 'create', 'result' => 'created'], $file);
            $this->assertFileExists($file['path']);
        }

        $paths = array_column($document['files'], 'path');
        $this->assertSame(array_values(array_unique($paths)), $paths, 'A path is listed twice.');

        $created = array_filter(
            $this->createdFiles($before, $this->listing(base_path('modules'))),
            fn (string $path) => str_starts_with($path, base_path('modules/Blog/')),
        );
        $this->assertEqualsCanonicalizing($created, $paths);
    }

    public function test_module_make_force_json_deletes_the_module_and_creates_its_files_again()
    {
        $this->createModule('Blog');
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $this->backdate(base_path('modules/Blog'));
        $before = $this->listing(base_path('modules/Blog'));

        [$code, $document] = $this->runJson('module:make', ['name' => ['Blog'], '--force' => true, '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame([], $document['errors']);
        $this->assertSame(['path' => base_path('modules/Blog'), 'exists' => true, 'action' => 'delete', 'result' => 'deleted'], $document['files'][0]);
        $this->assertFileDoesNotExist($this->getModuleAppPath('Blog').'/Models/Post.php');

        $files = array_slice($document['files'], 1);
        $after = $this->listing(base_path('modules/Blog'));

        foreach ($files as $file) {
            // The file is written again into the deleted folder: it existed before the run when it was in it.
            $this->assertSame('create', $file['action'], $file['path']);
            $this->assertSame('created', $file['result'], $file['path']);
            $this->assertSame(array_key_exists($file['path'], $before), $file['exists'], $file['path']);
        }

        $this->assertContains(['path' => base_path('modules/Blog/module.json'), 'exists' => true, 'action' => 'create', 'result' => 'created'], $files);

        // Every file in the module now was written by this run: new, or deleted and written again.
        $this->assertEqualsCanonicalizing(
            $this->files($after),
            array_merge($this->createdFiles($before, $after), $this->changedFiles($before, $after)),
            'A file of the module was not written by the run.',
        );
        $this->assertEqualsCanonicalizing($this->files($after), array_column($files, 'path'));
    }

    public function test_module_make_json_for_an_existing_module_reports_the_error()
    {
        $this->createModule('Blog');

        [$code, $document] = $this->runJson('module:make', ['name' => ['Blog'], '--json' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame(['command' => 'module:make', 'dry_run' => false, 'files' => [], 'errors' => ['Module [Blog] already exists!']], $document);
    }

    public function test_model_all_json_with_an_existing_file_reports_each_result()
    {
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->artisan('module:make-request', ['name' => 'PostRequest', 'module' => 'Blog']);
        $this->artisan('module:make-request', ['name' => 'PostRequest', 'module' => 'Shop']);
        $request = $this->getModuleAppPath('Blog').'/Http/Requests/PostRequest.php';

        $before = $this->listing(base_path('modules'));

        [$code, $document] = $this->runJson('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true, '--json' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame($this->textExitCode('module:make-model', ['model' => 'Post', 'module' => 'Shop', '--all' => true]), $code);
        $this->assertSame(["File : {$request} already exists."], $document['errors']);
        $this->assertCount(8, $document['files']);
        $this->assertSame(['path' => $request, 'exists' => true, 'action' => 'keep', 'result' => 'exists'], $document['files'][4]);

        $after = $this->listing(base_path('modules/Blog'));
        $created = array_filter($document['files'], fn (array $file) => $file['result'] === 'created');

        $this->assertCount(7, $created);
        $this->assertEqualsCanonicalizing($this->createdFiles($before, $after), array_column($created, 'path'));
        $this->assertSame([], $this->changedFiles($before, $after), 'The existing file was changed.');
    }

    public function test_force_reports_a_replaced_file()
    {
        $this->createModule('Blog');
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $model = $this->getModuleAppPath('Blog').'/Models/Post.php';

        [$code, $document] = $this->runJson('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--force' => true, '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame([['path' => $model, 'exists' => true, 'action' => 'replace', 'result' => 'replaced']], $document['files']);
    }

    public function test_a_write_that_returns_false_is_reported_as_failed()
    {
        $failing = base_path('modules/Blog/routes/web.php');

        $this->app->instance(Filesystem::class, new class($failing) extends Filesystem
        {
            public function __construct(private string $failing) {}

            public function put($path, $contents, $lock = false)
            {
                return str_replace('\\', '/', (string) preg_replace('#/+#', '/', $path)) === $this->failing ? false : parent::put($path, $contents, $lock);
            }
        });

        [$code, $document] = $this->runJson('module:make', ['name' => ['Blog'], '--json' => true]);

        // The text run ignores the result of put() and succeeds; so does the JSON run.
        $this->assertSame(0, $code);
        $this->assertContains(['path' => $failing, 'exists' => false, 'action' => 'create', 'result' => 'failed'], $document['files']);
        $this->assertSame(["Could not write file {$failing}."], $document['errors']);
        $this->assertFileDoesNotExist($failing);
    }

    public function test_a_write_that_throws_is_reported_as_failed_and_the_exception_is_kept()
    {
        $this->createModule('Blog');
        $this->readOnly = $this->getModuleAppPath('Blog').'/Models';
        chmod($this->readOnly, 0555);
        $model = $this->readOnly.'/Post.php';

        try {
            Artisan::call('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--json' => true]);
            $this->fail('The failed write did not throw.');
        } catch (\ErrorException $e) {
            $this->assertStringContainsString('Permission denied', $e->getMessage());
        }

        $output = Artisan::output();
        $this->assertCount(1, explode("\n", rtrim($output, "\n")));

        $document = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([['path' => $model, 'exists' => false, 'action' => 'create', 'result' => 'failed']], $document['files']);
        $this->assertCount(1, $document['errors']);
        $this->assertStringContainsString('Permission denied', $document['errors'][0]);
        $this->assertFileDoesNotExist($model);
    }

    public function test_composer_dump_autoload_failure_is_in_the_errors()
    {
        Process::fake([
            '*dump-autoload*' => Process::result(
                errorOutput: "\nIn JsonFile.php line 382:\n\n  \"./Modules/Blog/composer.json\" does not contain valid JSON\n\n\ndump-autoload [-o|--optimize] [-a|--classmap-authoritative] [--dev] [--no-dev]\n\n",
                exitCode: 1,
            ),
        ]);

        [$code, $document] = $this->runJson('module:make', ['name' => ['Blog'], '--json' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame([
            'composer dump-autoload failed.',
            "In JsonFile.php line 382:\n\n  \"./Modules/Blog/composer.json\" does not contain valid JSON",
        ], $document['errors']);
        $this->assertNotEmpty($document['files']);
    }

    public function test_stdout_has_only_the_json_line_and_messages_go_to_stderr()
    {
        Process::fake();

        $output = new MemoryConsoleOutput(decorated: true);

        $code = $this->runOnConsole(['command' => 'module:make', 'name' => ['Blog'], '--json' => true, '--ansi' => true], $output);

        $this->assertSame(0, $code);
        $this->assertCount(1, explode("\n", rtrim($output->stdout(), "\n")));
        $this->assertStringNotContainsString("\e[", $output->stdout());
        $this->assertSame('module:make', json_decode($output->stdout(), true, flags: JSON_THROW_ON_ERROR)['command']);

        $stderr = $output->stderr();
        $this->assertStringContainsString('Generating file', $stderr);
        $this->assertStringContainsString('created successfully', $stderr);
        $this->assertStringContainsString("\e[", $stderr);
    }

    public function test_the_health_indicator_hint_goes_to_stderr()
    {
        $this->createModule('Blog');

        $output = new MemoryConsoleOutput;

        $code = $this->runOnConsole(['command' => 'module:make-health-indicator', 'name' => 'Queue', 'module' => 'Blog', '--json' => true], $output);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Register it as [module:blog:queue]', $output->stderr());
        $this->assertStringNotContainsString('Register it as', $output->stdout());
        $this->assertCount(1, json_decode($output->stdout(), true, flags: JSON_THROW_ON_ERROR)['files']);
    }

    public function test_text_output_is_unchanged_without_json()
    {
        $this->createModule('Blog');

        $code = Artisan::call('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Generating file', $output);
        $this->assertStringNotContainsString('{"command"', $output);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function generators(): array
    {
        return array_combine(
            array_keys(self::GENERATOR_ARGUMENTS),
            array_map(fn (string $name) => [$name], array_keys(self::GENERATOR_ARGUMENTS)),
        );
    }

    #[DataProvider('generators')]
    public function test_every_generator_reports_the_files_it_wrote(string $command)
    {
        $this->createModule('Blog');
        $this->createModule('Shop');

        $before = $this->listing(base_path('modules/Blog'));

        [$code, $document] = $this->runJson($command, self::GENERATOR_ARGUMENTS[$command] + ['module' => 'Blog', '--json' => true]);

        $this->assertSame($this->textExitCode($command, self::GENERATOR_ARGUMENTS[$command] + ['module' => 'Shop']), $code);
        $this->assertSame(0, $code);
        $this->assertSame($command, $document['command']);
        $this->assertFalse($document['dry_run']);
        $this->assertSame([], $document['errors']);
        $this->assertNotEmpty($document['files']);

        $after = $this->listing(base_path('modules/Blog'));
        $results = array_column($document['files'], 'result', 'path');

        foreach ($document['files'] as $file) {
            $this->assertFileExists($file['path']);
        }

        $this->assertEqualsCanonicalizing($this->createdFiles($before, $after), array_keys($results, 'created', true));

        foreach (array_keys($results, 'replaced', true) as $path) {
            $this->assertArrayHasKey($path, $before);
        }

        $this->assertSame([], array_diff($results, ['created', 'replaced']));
    }

    /**
     * The exit code of the same command without --json.
     *
     * @param  array<string, mixed>  $arguments
     */
    private function textExitCode(string $command, array $arguments): int
    {
        return Artisan::call($command, $arguments);
    }

    /**
     * Run a command as the console does, with separate standard output and error streams.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function runOnConsole(array $parameters, MemoryConsoleOutput $output): int
    {
        $command = Artisan::all()[$parameters['command']];
        unset($parameters['command']);

        $input = new ArrayInput($parameters);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }
}
