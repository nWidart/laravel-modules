<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ModelPruneCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    private ?Closure $autoloader = null;

    /**
     * @var array<string, string>
     */
    private array $autoload_map = [];

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

        if ($this->repository->find('Shop')) {
            $this->repository->delete('Shop');
        }

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

    public function test_it_fails_for_an_unknown_module()
    {
        $this->expectException(ModuleNotFoundException::class);
        $this->expectExceptionMessage('Module [Nope] does not exist!');

        $this->artisan('module:prune', ['module' => ['Nope'], '--no-interaction' => true]);
    }

    public function test_it_fails_for_an_unknown_module_before_pruning_a_known_one()
    {
        $this->createPrunableModel('Modules\Blog\Models', 'KnownPost', 'app/Models');

        try {
            $this->artisan('module:prune', ['module' => ['Blog', 'Nope'], '--no-interaction' => true]);
            $this->fail('Expected a ModuleNotFoundException.');
        } catch (ModuleNotFoundException $e) {
            $this->assertSame('Module [Nope] does not exist!', $e->getMessage());
        }

        $this->assertSame(1, DB::table('knownposts')->count());
    }

    public function test_it_fails_for_an_unknown_module_when_models_are_given()
    {
        $this->createPrunableModel('Modules\Blog\Models', 'GivenPost', 'app/Models');

        try {
            $this->artisan('module:prune', [
                'module' => ['Nope'],
                '--model' => ['Modules\Blog\Models\GivenPost'],
                '--no-interaction' => true,
            ]);
            $this->fail('Expected a ModuleNotFoundException.');
        } catch (ModuleNotFoundException $e) {
            $this->assertSame('Module [Nope] does not exist!', $e->getMessage());
        }

        $this->assertSame(1, DB::table('givenposts')->count());
    }

    #[DataProvider('casedModuleNameProvider')]
    public function test_it_resolves_a_differently_cased_module_name_to_the_module_path_and_namespace(string $name)
    {
        $this->createPrunableModel('Modules\Blog\Models', 'LowerPost', 'app/Models');

        $code = $this->artisan('module:prune', ['module' => [$name], '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Modules\Blog\Models\LowerPost] records will be pruned.', $this->commandOutput());
    }

    public static function casedModuleNameProvider(): array
    {
        // On a case-sensitive filesystem the raw argument would point at a missing directory.
        // "BLOG" also fails on a case-insensitive one, where the old namespace came from the argument.
        return [
            'lowercase' => ['blog'],
            'uppercase' => ['BLOG'],
        ];
    }

    public function test_all_prunes_enabled_modules_only_and_skips_disabled_modules()
    {
        $this->createModule('Shop');
        $this->repository->find('Shop')->disable();
        $this->createPrunableModel('Modules\Blog\Models', 'EnabledPost', 'app/Models');
        $this->createPrunableModel('Modules\Shop\Models', 'DisabledOrder', 'app/Models', 'Shop');

        $code = $this->artisan('module:prune', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $output = $this->commandOutput();
        $this->assertStringContainsString('Modules\Blog\Models\EnabledPost', $output);
        $this->assertStringNotContainsString('DisabledOrder', $output);
        $this->assertSame(0, DB::table('enabledposts')->count());
        $this->assertSame(1, DB::table('disabledorders')->count());
    }

    public function test_all_with_pretend_lists_enabled_modules_only()
    {
        $this->createModule('Shop');
        $this->repository->find('Shop')->disable();
        $this->createPrunableModel('Modules\Blog\Models', 'PretendPost', 'app/Models');
        $this->createPrunableModel('Modules\Shop\Models', 'PretendOrder', 'app/Models', 'Shop');

        $code = $this->artisan('module:prune', ['--all' => true, '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $output = $this->commandOutput();
        $this->assertStringContainsString('1 [Modules\Blog\Models\PretendPost] records will be pruned.', $output);
        $this->assertStringNotContainsString('PretendOrder', $output);
    }

    public function test_it_prunes_a_disabled_module_given_by_name()
    {
        $this->createModule('Shop');
        $this->repository->find('Shop')->disable();
        $this->createPrunableModel('Modules\Shop\Models', 'NamedOrder', 'app/Models', 'Shop');

        $code = $this->artisan('module:prune', ['module' => ['Shop'], '--pretend' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('1 [Modules\Shop\Models\NamedOrder] records will be pruned.', $this->commandOutput());
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
     * Write a prunable model with one prunable row into a module.
     */
    private function createPrunableModel(string $namespace, string $class, string $path, string $module = 'Blog'): void
    {
        $this->createModel($namespace, $class, $path, module: $module);

        $table = strtolower($class).'s';
        Schema::create($table, fn (Blueprint $table) => $table->id());
        DB::table($table)->insert(['id' => 1]);
    }

    /**
     * Write a model into a module and autoload the module root namespace from it.
     */
    private function createModel(string $namespace, string $class, string $path, bool $prunable = true, string $module = 'Blog'): void
    {
        $prunable_trait = $prunable ? 'use \Illuminate\Database\Eloquent\Prunable;' : '';
        $table = strtolower($class).'s';

        $this->app['files']->ensureDirectoryExists(base_path("modules/{$module}/{$path}"));
        $this->app['files']->put(base_path("modules/{$module}/{$path}/{$class}.php"), <<<PHP
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

        $root_namespace = str_contains($namespace, 'Custom') ? 'Custom\Modules' : 'Modules';

        $this->registerAutoloader(
            "{$root_namespace}\\{$module}\\",
            base_path("modules/{$module}/".strtok($path, '/'))
        );
    }

    /**
     * Map a PSR-4 prefix to a directory, as the module's composer.json does in an app.
     */
    private function registerAutoloader(string $prefix, string $directory): void
    {
        $this->autoload_map[$prefix] ??= $directory;

        if ($this->autoloader) {
            return;
        }

        $this->autoloader = function (string $class) {
            foreach ($this->autoload_map as $prefix => $directory) {
                if (! str_starts_with($class, $prefix)) {
                    continue;
                }

                $file = $directory.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

                if (is_file($file)) {
                    require $file;

                    return;
                }
            }
        };

        spl_autoload_register($this->autoloader);
    }
}
