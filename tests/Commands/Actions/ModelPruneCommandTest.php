<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ModelPruneCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    private ?Closure $autoloader = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        if ($this->autoloader) {
            spl_autoload_unregister($this->autoloader);
        }

        $this->repository->delete('Blog');
        parent::tearDown();
    }

    public function test_it_checks_all_modules_with_all_without_interaction()
    {
        $this->app['files']->ensureDirectoryExists(base_path('modules/Blog/app/Models'));

        $code = $this->artisan('module:prune', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No prunable models found.', $this->commandOutput());
    }

    public function test_it_reports_nothing_prunable_with_all_when_no_module_has_a_models_directory()
    {
        // BaseTestCase enables every generator, so module:make created the models directory.
        $this->app['files']->deleteDirectory(base_path('modules/Blog/app/Models'));

        $code = $this->artisan('module:prune', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No prunable models found.', $this->commandOutput());
    }

    public function test_it_reports_nothing_prunable_for_a_named_module_without_a_models_directory()
    {
        // BaseTestCase enables every generator, so module:make created the models directory.
        $this->app['files']->deleteDirectory(base_path('modules/Blog/app/Models'));

        $code = $this->artisan('module:prune', ['module' => ['Blog'], '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No prunable models found.', $this->commandOutput());
    }

    public function test_it_reports_nothing_prunable_for_an_unknown_module()
    {
        $code = $this->artisan('module:prune', ['module' => ['Unknown'], '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No prunable models found.', $this->commandOutput());
    }

    public function test_it_discovers_prunable_models_in_a_named_module()
    {
        $this->createPrunableModel('Modules\Blog\Models', 'NamedPost', 'app/Models');
        $this->createModel('Modules\Blog\Models', 'NamedTag', 'app/Models', prunable: false);

        $code = $this->artisan('module:prune', ['module' => ['Blog'], '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $output = $this->commandOutput();
        $this->assertStringContainsString('1 [Modules\Blog\Models\NamedPost] records will be pruned.', $output);
        $this->assertStringNotContainsString('NamedTag', $output);
    }

    public function test_it_discovers_prunable_models_in_all_modules()
    {
        $this->createPrunableModel('Modules\Blog\Models', 'AllPost', 'app/Models');

        $code = $this->artisan('module:prune', ['--all' => true, '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Modules\Blog\Models\AllPost] records will be pruned.', $this->commandOutput());
    }

    public function test_it_discovers_prunable_models_in_subdirectories()
    {
        $this->createPrunableModel('Modules\Blog\Models\Archive', 'ArchivedPost', 'app/Models/Archive');

        $code = $this->artisan('module:prune', ['module' => ['Blog'], '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Modules\Blog\Models\Archive\ArchivedPost] records will be pruned.', $this->commandOutput());
    }

    public function test_it_discovers_prunable_models_with_a_custom_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'src/');
        $this->app['config']->set('modules.paths.generator.model.path', 'src/Models');
        $this->createPrunableModel('Modules\Blog\Models', 'SrcPost', 'src/Models');

        $code = $this->artisan('module:prune', ['--all' => true, '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Modules\Blog\Models\SrcPost] records will be pruned.', $this->commandOutput());
    }

    public function test_it_only_strips_the_app_folder_as_a_prefix()
    {
        // ltrim('app/Models', 'app/') would strip a character set and break this namespace.
        $this->app['config']->set('modules.paths.generator.model.path', 'app/Appointments');
        $this->createPrunableModel('Modules\Blog\Appointments', 'Appointment', 'app/Appointments');

        $code = $this->artisan('module:prune', ['module' => ['Blog'], '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Modules\Blog\Appointments\Appointment] records will be pruned.', $this->commandOutput());
    }

    public function test_it_discovers_prunable_models_with_a_custom_namespace()
    {
        $this->app['config']->set('modules.namespace', 'Custom\Modules');
        $this->createPrunableModel('Custom\Modules\Blog\Models', 'CustomPost', 'app/Models');

        $code = $this->artisan('module:prune', ['module' => ['Blog'], '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Custom\Modules\Blog\Models\CustomPost] records will be pruned.', $this->commandOutput());
    }

    public function test_it_excludes_models_given_with_except()
    {
        $this->createPrunableModel('Modules\Blog\Models', 'ExceptPost', 'app/Models');

        $code = $this->artisan('module:prune', [
            'module' => ['Blog'],
            '--except' => ['Modules\Blog\Models\ExceptPost'],
            '--pretend' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No prunable models found.', $this->commandOutput());
    }

    public function test_it_does_not_combine_model_and_except()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The --model and --except options cannot be combined.');

        $this->artisan('module:prune', [
            'module' => ['Blog'],
            '--model' => ['Modules\Blog\Models\Post'],
            '--except' => ['Modules\Blog\Models\Comment'],
            '--no-interaction' => true,
        ]);
    }

    private function commandOutput(): string
    {
        return $this->app[Kernel::class]->output();
    }

    /**
     * Write a prunable model with one prunable row into the Blog module.
     */
    private function createPrunableModel(string $namespace, string $class, string $path): void
    {
        $this->createModel($namespace, $class, $path);

        $table = strtolower($class).'s';
        Schema::create($table, fn (Blueprint $table) => $table->id());
        DB::table($table)->insert(['id' => 1]);
    }

    /**
     * Write a model into the Blog module and autoload the module root namespace from it.
     */
    private function createModel(string $namespace, string $class, string $path, bool $prunable = true): void
    {
        $prunable_trait = $prunable ? 'use \Illuminate\Database\Eloquent\Prunable;' : '';
        $table = strtolower($class).'s';

        $this->app['files']->ensureDirectoryExists(base_path("modules/Blog/{$path}"));
        $this->app['files']->put(base_path("modules/Blog/{$path}/{$class}.php"), <<<PHP
            <?php

            namespace {$namespace};

            use Illuminate\Database\Eloquent\Builder;
            use Illuminate\Database\Eloquent\Model;

            class {$class} extends Model
            {
                {$prunable_trait}

                protected \$table = '{$table}';

                public function prunable(): Builder
                {
                    return static::query();
                }
            }
            PHP);

        $this->registerAutoloader(
            str_contains($namespace, 'Custom') ? 'Custom\Modules\Blog\\' : 'Modules\Blog\\',
            base_path('modules/Blog/'.strtok($path, '/'))
        );
    }

    /**
     * Map a PSR-4 prefix to a directory, as the module's composer.json does in an app.
     */
    private function registerAutoloader(string $prefix, string $directory): void
    {
        if ($this->autoloader) {
            return;
        }

        $this->autoloader = function (string $class) use ($prefix, $directory) {
            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $file = $directory.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

            if (is_file($file)) {
                require $file;
            }
        };

        spl_autoload_register($this->autoloader);
    }
}
