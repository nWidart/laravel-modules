<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    use PathNamespace {
        module_path as __module_path;
        module_app_path as __module_app_path;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }
        // $this->setUpDatabase();
    }

    private function resetDatabase()
    {
        $this->artisan('migrate:reset', [
            '--database' => 'sqlite',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelModulesServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $module_config = require __DIR__.'/../config/config.php';

        // enable all generators
        array_walk($module_config['paths']['generator'], function (&$item) {
            $item['generate'] = true;
        });

        $app['config']->set('app.asset_url', null);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('modules.paths.modules', base_path('modules'));
        $app['config']->set('modules.paths', [
            'modules' => base_path('modules'),
            'assets' => public_path('modules'),
            'migration' => base_path('database/migrations'),
            'app_folder' => $module_config['paths']['app_folder'],
            'generator' => $module_config['paths']['generator'],
        ]);

        $app['config']->set('modules.composer-output', true);

        $app['config']->set('modules.commands', ConsoleServiceProvider::defaultCommands()->toArray());
    }

    protected function setUpDatabase()
    {
        $this->resetDatabase();
    }

    protected function createModule(string $module = 'Blog'): int
    {
        return $this->artisan('module:make', ['name' => [$module]]);
    }

    /**
     * @deprecated use module_app_path() instead.
     */
    protected function getModuleAppPath(string $moduleName = 'Blog'): string
    {
        return base_path("modules/$moduleName/").rtrim(config('modules.paths.app_folder'), '/');
    }

    /**
     * @deprecated use module_path() instead.
     */
    protected function getModuleBasePath(string $moduleName = 'Blog'): string
    {
        return base_path("modules/$moduleName");
    }

    protected function module_path(?string $path = null, string $module = 'Blog'): string
    {
        return $this->modules_path("{$module}/{$path}");
    }

    protected function module_app_path(?string $path = null, string $module = 'Blog'): string
    {
        return $this->module_path($this->app_path($path), $module);
    }
}
