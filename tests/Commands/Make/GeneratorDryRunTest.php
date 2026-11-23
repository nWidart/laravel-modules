<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use FilesystemIterator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Commands\Actions\ListGeneratorsCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class GeneratorDryRunTest extends BaseTestCase
{
    /**
     * Minimal valid arguments for every package generator except module:make, which
     * the module:make tests cover. The module argument is added by the test.
     *
     * @var array<string, array<string, mixed>>
     */
    private const GENERATOR_ARGUMENTS = [
        'module:make-action' => ['name' => 'PublishPost'],
        'module:make-cast' => ['name' => 'Money'],
        'module:make-channel' => ['name' => 'PostChannel'],
        'module:make-class' => ['name' => 'PostPresenter'],
        'module:make-command' => ['name' => 'PublishCommand'],
        'module:make-component' => ['name' => 'Alert'],
        'module:make-component-view' => ['name' => 'alert'],
        'module:make-config' => ['name' => 'posts'],
        'module:make-controller' => ['controller' => 'PostController'],
        'module:make-enum' => ['name' => 'Status'],
        'module:make-event' => ['name' => 'PostPublished'],
        // module:make writes this provider already.
        'module:make-event-provider' => ['--force' => true],
        'module:make-exception' => ['name' => 'PostException'],
        'module:make-factory' => ['name' => 'Post'],
        'module:make-health-indicator' => ['name' => 'Queue'],
        'module:make-helper' => ['name' => 'PostHelper'],
        'module:make-inertia-component' => ['name' => 'PostCard'],
        'module:make-inertia-page' => ['name' => 'Index'],
        'module:make-interface' => ['name' => 'PostContract'],
        'module:make-job' => ['name' => 'PublishPostJob'],
        'module:make-job-middleware' => ['name' => 'RateLimited'],
        'module:make-listener' => ['name' => 'NotifyAuthor'],
        'module:make-mail' => ['name' => 'PostMail'],
        'module:make-middleware' => ['name' => 'EnsurePostIsPublished'],
        'module:make-migration' => ['name' => 'create_posts_table'],
        'module:make-model' => ['model' => 'Post', '--all' => true],
        'module:make-notification' => ['name' => 'PostNotification'],
        'module:make-observer' => ['name' => 'Post'],
        'module:make-policy' => ['name' => 'PostPolicy'],
        'module:make-provider' => ['name' => 'PostServiceProvider'],
        'module:make-replacement' => ['name' => 'VendorName'],
        'module:make-repository' => ['name' => 'PostRepository'],
        'module:make-request' => ['name' => 'PostRequest'],
        'module:make-resource' => ['name' => 'PostResource'],
        'module:make-rule' => ['name' => 'Slug'],
        'module:make-scope' => ['name' => 'PublishedScope'],
        'module:make-seed' => ['name' => 'PostSeeder'],
        'module:make-service' => ['name' => 'PostService'],
        'module:make-test' => ['name' => 'PostTest'],
        'module:make-trait' => ['name' => 'HasPosts'],
        'module:make-view' => ['name' => 'posts/index'],
        // module:make writes this provider already.
        'module:route-provider' => ['--force' => true],
    ];

    protected function tearDown(): void
    {
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
            'api' => [['--api' => true]],
            'plain' => [['--plain' => true]],
            'inertia' => [['--inertia' => true]],
        ];
    }

    /**
     * @param  array<string, bool>  $options
     */
    #[DataProvider('moduleTypes')]
    public function test_module_make_dry_run_lists_the_files_the_real_run_creates(array $options)
    {
        $before = $this->listing(base_path());

        [$code, $document] = $this->dryRunJson('module:make', ['name' => ['Blog']] + $options);

        $this->assertSame(0, $code);
        $this->assertSame($before, $this->listing(base_path()), 'The dry run wrote to the application.');
        $this->assertNull($this->app[RepositoryInterface::class]->find('Blog'));
        $this->assertSame('module:make', $document['command']);
        $this->assertTrue($document['dry_run']);
        $this->assertSame([], $document['errors']);

        foreach ($document['files'] as $file) {
            $this->assertSame(['path', 'exists', 'action'], array_keys($file));
            $this->assertFalse($file['exists'], $file['path']);
            $this->assertSame('create', $file['action'], $file['path']);
            $this->assertStringNotContainsString('..', $file['path']);
            $this->assertStringNotContainsString('//', $file['path']);
        }

        $paths = array_column($document['files'], 'path');
        $this->assertSame(array_values(array_unique($paths)), $paths, 'A path is listed twice.');

        // module.json comes after the folders' .gitkeep files and before the stub files.
        $modulePath = base_path('modules/Blog');
        $moduleJson = array_search("{$modulePath}/module.json", $paths, true);
        $this->assertIsInt($moduleJson);
        $this->assertStringEndsWith('/.gitkeep', $paths[$moduleJson - 1]);

        $modulesBefore = $this->listing(base_path('modules'));
        $this->assertSame(0, $this->artisan('module:make', ['name' => ['Blog']] + $options));

        $this->assertEqualsCanonicalizing($this->createdFiles($modulesBefore, $this->listing(base_path('modules'))), $paths);
    }

    public function test_model_all_dry_run_lists_the_files_the_real_run_creates()
    {
        $this->createModule('Blog');

        $before = $this->listing(base_path());

        [$code, $document] = $this->dryRunJson('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true]);

        $this->assertSame(0, $code);
        $this->assertSame($before, $this->listing(base_path()), 'The dry run wrote to the application.');

        $appPath = $this->getModuleAppPath('Blog');
        $paths = array_column($document['files'], 'path');

        // The model first, then the files of the generators it calls, in the order it calls them.
        $this->assertSame("{$appPath}/Models/Post.php", $paths[0]);
        $this->assertSame("{$appPath}/Http/Controllers/PostController.php", $paths[1]);
        $this->assertCount(8, $paths);

        $modulesBefore = $this->listing(base_path('modules'));
        $this->assertSame(0, $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true]));

        $this->assertEqualsCanonicalizing(
            $this->withoutMigrationDates($this->createdFiles($modulesBefore, $this->listing(base_path('modules')))),
            $this->withoutMigrationDates($paths),
        );
    }

    public function test_model_all_dry_run_keeps_an_existing_file_and_fails_like_the_real_run()
    {
        $this->createModule('Blog');
        $this->artisan('module:make-controller', ['controller' => 'PostController', 'module' => 'Blog']);
        $controller = $this->getModuleAppPath('Blog').'/Http/Controllers/PostController.php';

        $before = $this->listing(base_path());

        [$code, $document] = $this->dryRunJson('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame($before, $this->listing(base_path()));
        $this->assertSame(['path' => $controller, 'exists' => true, 'action' => 'keep'], $document['files'][1]);
        $this->assertSame(["File : {$controller} already exists."], $document['errors']);
        $this->assertCount(8, $document['files']);

        $this->assertSame(E_ERROR, $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true]));
    }

    public function test_force_replaces_an_existing_file()
    {
        $this->createModule('Blog');
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $model = $this->getModuleAppPath('Blog').'/Models/Post.php';

        [$code, $document] = $this->dryRunJson('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertSame([['path' => $model, 'exists' => true, 'action' => 'replace']], $document['files']);
    }

    public function test_module_make_dry_run_for_an_existing_module_fails_like_the_real_run()
    {
        $this->createModule('Blog');
        $before = $this->listing(base_path());

        [$code, $document] = $this->dryRunJson('module:make', ['name' => ['Blog']]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame($before, $this->listing(base_path()));
        $this->assertSame([], $document['files']);
        $this->assertSame(['Module [Blog] already exists!'], $document['errors']);
    }

    public function test_module_make_force_dry_run_deletes_the_module_first_and_deletes_nothing()
    {
        $this->createModule('Blog');
        $before = $this->listing(base_path());

        [$code, $document] = $this->dryRunJson('module:make', ['name' => ['Blog'], '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertSame($before, $this->listing(base_path()), 'The dry run changed the existing module.');
        $this->assertSame(['path' => base_path('modules/Blog'), 'exists' => true, 'action' => 'delete'], $document['files'][0]);

        foreach (array_slice($document['files'], 1) as $file) {
            $this->assertSame('create', $file['action'], $file['path']);
        }

        $this->assertContains(['path' => base_path('modules/Blog/module.json'), 'exists' => true, 'action' => 'create'], $document['files']);
    }

    public function test_module_make_dry_run_lists_every_module()
    {
        [$code, $document] = $this->dryRunJson('module:make', ['name' => ['Blog', 'Shop']]);

        $this->assertSame(0, $code);

        $paths = array_column($document['files'], 'path');
        $this->assertContains(base_path('modules/Blog/app/Providers/BlogServiceProvider.php'), $paths);
        $this->assertContains(base_path('modules/Shop/app/Providers/ShopServiceProvider.php'), $paths);
        $this->assertDirectoryDoesNotExist(base_path('modules/Blog'));
        $this->assertDirectoryDoesNotExist(base_path('modules/Shop'));
    }

    public function test_module_make_dry_run_reports_an_invalid_name()
    {
        [$code, $document] = $this->dryRunJson('module:make', ['name' => ['1Blog']]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame([], $document['files']);
        $this->assertSame(['The module name [1Blog] is not valid. Use letters, digits, "-", "_" or spaces, starting with a letter.'], $document['errors']);
    }

    public function test_module_make_dry_run_reports_invalid_generator_paths()
    {
        $this->app['config']->set('modules.paths.generator.model.path', 'Models');

        [$code, $document] = $this->dryRunJson('module:make', ['name' => ['Blog']]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame([], $document['files']);
        $this->assertCount(1, $document['errors']);
        $this->assertStringStartsWith('Generator paths must be inside paths.app_folder [app/]', $document['errors'][0]);
        $this->assertStringEndsWith('model (Models)', $document['errors'][0]);
        $this->assertDirectoryDoesNotExist(base_path('modules/Blog'));
    }

    public function test_generator_dry_run_reports_an_unknown_module()
    {
        [$code, $document] = $this->dryRunJson('module:make-model', ['model' => 'Post', 'module' => 'Missing']);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame(['command' => 'module:make-model', 'dry_run' => true, 'files' => [], 'errors' => ['Module [Missing] does not exist.']], $document);
    }

    public function test_generator_dry_run_reports_a_path_traversal()
    {
        $this->createModule('Blog');

        [$code, $document] = $this->dryRunJson('module:make-model', ['model' => '../Post', 'module' => 'Blog']);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame([], $document['files']);
        $this->assertSame(['The model [../Post] must not be an absolute path or contain ".." segments.'], $document['errors']);
    }

    public function test_generator_dry_run_reports_conflicting_inertia_frontends()
    {
        $this->createModule('Blog');

        [$code, $document] = $this->dryRunJson('module:make-inertia-page', ['name' => 'Index', 'module' => 'Blog', '--vue' => true, '--react' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame([], $document['files']);
        $this->assertSame(['The --vue and --react options cannot be used together. Pass only one of --vue, --react or --svelte.'], $document['errors']);
    }

    public function test_json_without_dry_run_fails_and_writes_nothing()
    {
        $before = $this->listing(base_path());

        [$code, $document] = $this->runJson('module:make', ['name' => ['Blog'], '--json' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame($before, $this->listing(base_path()));
        $this->assertSame(['command' => 'module:make', 'dry_run' => false, 'files' => [], 'errors' => ['The --json option needs --dry-run.']], $document);
    }

    public function test_dry_run_without_json_prints_one_line_per_file()
    {
        $this->createModule('Blog');
        $this->artisan('module:make-controller', ['controller' => 'PostController', 'module' => 'Blog']);
        $before = $this->listing(base_path());

        $code = Artisan::call('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true, '--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(E_ERROR, $code);
        $this->assertSame($before, $this->listing(base_path()));
        $this->assertStringContainsString('Dry run: these files would be written. Nothing was written.', $output);
        $this->assertStringContainsString('  create  modules/Blog/app/Models/Post.php', $output);
        $this->assertStringContainsString('  keep  modules/Blog/app/Http/Controllers/PostController.php (exists)', $output);
        $this->assertStringContainsString('already exists.', $output);
        $this->assertStringNotContainsString('Generating file', $output);
        $this->assertStringNotContainsString('DONE', $output);
    }

    public function test_a_second_call_of_the_command_is_not_in_json_mode()
    {
        $this->createModule('Blog');

        $this->dryRunJson('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        Artisan::call('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--dry-run' => true]);

        $this->assertStringContainsString('  create  modules/Blog/app/Models/Post.php', Artisan::output());
    }

    public function test_dry_run_json_stdout_has_no_ansi_codes()
    {
        $this->createModule('Blog');

        $stdout = fopen('php://memory', 'w+');
        $output = new StreamOutput($stdout, decorated: true);

        $code = $this->app[Kernel::class]->handle(
            new ArrayInput(['command' => 'module:make-model', 'model' => 'Post', 'module' => 'Blog', '--all' => true, '--dry-run' => true, '--json' => true, '--ansi' => true]),
            $output,
        );

        rewind($stdout);
        $contents = (string) stream_get_contents($stdout);

        $this->assertSame(0, $code);
        $this->assertStringNotContainsString("\e[", $contents);
        $this->assertCount(1, explode("\n", trim($contents)));
        $this->assertCount(8, json_decode($contents, true, flags: JSON_THROW_ON_ERROR)['files']);
    }

    public function test_every_generator_has_dry_run_arguments()
    {
        $generators = array_keys(ListGeneratorsCommand::GROUPS);
        $generators = array_values(array_diff($generators, ['module:make']));
        sort($generators);

        $listed = array_keys(self::GENERATOR_ARGUMENTS);
        sort($listed);

        $this->assertSame($generators, $listed, 'Add the new generator to GeneratorDryRunTest::GENERATOR_ARGUMENTS.');
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
    public function test_every_generator_dry_run_lists_the_files_the_real_run_creates(string $command)
    {
        $this->createModule('Blog');
        $arguments = self::GENERATOR_ARGUMENTS[$command] + ['module' => 'Blog'];

        $before = $this->listing(base_path());

        [$code, $document] = $this->dryRunJson($command, $arguments);

        $this->assertSame($before, $this->listing(base_path()), 'The dry run wrote to the application.');
        $this->assertSame($command, $document['command']);
        $this->assertSame([], $document['errors']);
        $this->assertNotEmpty($document['files'], 'The dry run lists no file.');

        $modulesBefore = $this->listing(base_path('modules'));
        $realCode = Artisan::call($command, $arguments);

        $this->assertSame($realCode, $code, 'The dry run exit code differs from the real run.');

        $created = array_filter($document['files'], fn (array $file) => $file['action'] === 'create');
        $this->assertEqualsCanonicalizing(
            $this->withoutMigrationDates($this->createdFiles($modulesBefore, $this->listing(base_path('modules')))),
            $this->withoutMigrationDates(array_column($created, 'path')),
        );

        foreach (array_diff_key($document['files'], $created) as $file) {
            $this->assertSame(['exists' => true, 'action' => 'replace'], ['exists' => $file['exists'], 'action' => $file['action']], $file['path']);
            $this->assertArrayHasKey($file['path'], $modulesBefore);
        }
    }

    /**
     * Run a command with --dry-run --json and decode its only output line.
     *
     * @param  array<string, mixed>  $arguments
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function dryRunJson(string $command, array $arguments): array
    {
        return $this->runJson($command, $arguments + ['--dry-run' => true, '--json' => true]);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function runJson(string $command, array $arguments): array
    {
        $code = Artisan::call($command, $arguments);
        $output = Artisan::output();

        $this->assertStringEndsWith("\n", $output);
        $this->assertCount(1, explode("\n", rtrim($output, "\n")), "Expected one JSON line, got: {$output}");

        return [$code, json_decode($output, true, flags: JSON_THROW_ON_ERROR)];
    }

    /**
     * Every file and folder below a path, with a hash of each file's contents and mtime.
     *
     * @return array<string, string>
     */
    private function listing(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        clearstatcache();

        $listing = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $listing[str_replace('\\', '/', $file->getPathname())] = $file->isDir() || $file->isLink()
                ? 'dir'
                : md5_file($file->getPathname()).'@'.$file->getMTime();
        }

        ksort($listing);

        return $listing;
    }

    /**
     * The files that are in the second listing and not in the first.
     *
     * @param  array<string, string>  $before
     * @param  array<string, string>  $after
     * @return list<string>
     */
    private function createdFiles(array $before, array $after): array
    {
        return array_keys(array_filter(
            array_diff_key($after, $before),
            fn (string $entry) => $entry !== 'dir',
        ));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function withoutMigrationDates(array $paths): array
    {
        return array_map(fn (string $path) => (string) preg_replace('/\d{4}_\d{2}_\d{2}_\d{6}_/', 'DATE_', $path), $paths);
    }
}
