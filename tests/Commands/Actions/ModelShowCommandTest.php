<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Nwidart\Modules\Commands\Actions\ModelShowCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ModelShowCommandTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Webapp');
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Webapp');
        parent::tearDown();
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
}
