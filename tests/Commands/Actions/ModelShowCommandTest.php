<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Commands\Actions\ModelShowCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ModelShowCommandTest extends BaseTestCase
{
    private ?Closure $autoloader = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Webapp');
    }

    protected function tearDown(): void
    {
        if ($this->autoloader) {
            spl_autoload_unregister($this->autoloader);
        }

        $repository = $this->app[RepositoryInterface::class];

        foreach (['Webapp', 'Shop'] as $module) {
            if ($repository->find($module)) {
                $repository->delete($module);
            }
        }

        parent::tearDown();
    }

    public function test_it_resolves_a_short_model_name_to_the_model_of_a_module()
    {
        $this->createModel('Webapp', 'Article');

        $code = $this->artisan('module:model-show', ['model' => 'Article', '--no-interaction' => true]);

        $output = $this->commandOutput();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Modules\\Webapp\\Models\\Article', $output);
        $this->assertStringContainsString('articles', $output);
    }

    public function test_it_resolves_a_model_name_in_a_subdirectory_of_the_models_directory()
    {
        $this->createModel('Webapp', 'Admin/Report');

        $code = $this->artisan('module:model-show', ['model' => 'Admin\\Report', '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Modules\\Webapp\\Models\\Admin\\Report', $this->commandOutput());
    }

    public function test_it_shows_a_model_given_by_its_fully_qualified_name()
    {
        $this->createModel('Webapp', 'Comment');

        $code = $this->artisan('module:model-show', ['model' => 'Modules\\Webapp\\Models\\Comment', '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Modules\\Webapp\\Models\\Comment', $this->commandOutput());
    }

    public function test_it_keeps_a_short_model_name_that_laravel_resolves_in_the_app()
    {
        $this->createModel('Webapp', 'Member', table: false);

        $app_model = app_path('Models/Member.php');
        $this->app['files']->put($app_model, <<<'PHP'
            <?php

            namespace App\Models;

            use Illuminate\Database\Eloquent\Model;

            class Member extends Model {}
            PHP);

        try {
            require_once $app_model;

            $code = $this->artisan('module:model-show', ['model' => 'Member', '--no-interaction' => true]);
        } finally {
            $this->app['files']->delete($app_model);
        }

        $output = $this->commandOutput();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('App\\Models\\Member', $output);
        $this->assertStringNotContainsString('Modules\\Webapp\\Models\\Member', $output);
    }

    public function test_it_fails_for_a_model_name_found_in_several_modules_without_interaction()
    {
        $this->createModule('Shop');
        $this->artisan('module:make-model', ['model' => 'Tag', 'module' => 'Webapp']);
        $this->artisan('module:make-model', ['model' => 'Tag', 'module' => 'Shop']);

        $code = $this->artisan('module:model-show', ['model' => 'Tag', '--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            'Model [Tag] is ambiguous, it exists in several modules: Modules\\Shop\\Models\\Tag, Modules\\Webapp\\Models\\Tag. Pass the fully qualified class name.',
            $this->commandOutput()
        );
    }

    public function test_it_asks_which_model_to_show_for_a_model_name_found_in_several_modules()
    {
        $this->createModule('Shop');
        $this->createModel('Webapp', 'Label');
        $this->createModel('Shop', 'Label', table: false);
        $this->mockConsoleOutput = true;

        $this->artisan('module:model-show', ['model' => 'Label'])
            ->expectsChoice(
                'Model [Label] exists in several modules. Select one',
                'Modules\\Webapp\\Models\\Label',
                ['Modules\\Shop\\Models\\Label', 'Modules\\Webapp\\Models\\Label'],
            )
            ->expectsOutputToContain('Modules\\Webapp\\Models\\Label')
            ->assertExitCode(0);
    }

    public function test_it_reports_laravels_error_for_an_unknown_model_name()
    {
        $code = $this->artisan('module:model-show', ['model' => 'Unknown', '--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Target class [App\\Models\\Unknown] does not exist.', $this->commandOutput());
    }

    public function test_it_finds_models_of_a_module_whose_name_contains_the_app_folder()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Webapp']);

        $models = $this->app->make(ModelShowCommand::class)->findModels('*');

        $this->assertSame(['Modules\\Webapp\\Models\\Post'], $models->all());
    }

    public function test_it_uses_the_configured_module_namespace()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Webapp']);
        $this->app['config']->set('modules.namespace', 'Domains');

        $models = $this->app->make(ModelShowCommand::class)->findModels('Po*');

        $this->assertSame(['Domains\\Webapp\\Models\\Post'], $models->all());
    }

    public function test_it_strips_a_custom_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'src/');
        $this->app['config']->set('modules.paths.generator.model.path', 'src/Models');

        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Webapp']);

        $models = $this->app->make(ModelShowCommand::class)->findModels('*');

        $this->assertSame(['Modules\\Webapp\\Models\\Post'], $models->all());
    }

    private function commandOutput(): string
    {
        return $this->app[Kernel::class]->output();
    }

    /**
     * Write a model into the models directory of a module, create its table,
     * and autoload the module namespace, as the module's composer.json does in an app.
     */
    private function createModel(string $module, string $model, bool $table = true): void
    {
        $this->artisan('module:make-model', ['model' => $model, 'module' => $module]);

        if ($table) {
            Schema::create(strtolower(class_basename($model)).'s', fn (Blueprint $table) => $table->id());
        }

        if ($this->autoloader) {
            return;
        }

        $this->autoloader = function (string $class) {
            if (! preg_match('/^Modules\\\\(\w+)\\\\(.+)$/', $class, $matches)) {
                return;
            }

            $file = $this->getModuleAppPath($matches[1]).'/'.str_replace('\\', '/', $matches[2]).'.php';

            if (is_file($file)) {
                require $file;
            }
        };

        spl_autoload_register($this->autoloader);
    }
}
