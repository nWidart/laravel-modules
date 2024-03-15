<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    public function setUp(): void
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
            'generator' => [
                'assets' => ['path' => 'Assets', 'generate' => true],
                'config' => ['path' => 'Config', 'generate' => true],
                'command' => ['path' => 'Console', 'generate' => true],
                'channels' => ['path' => 'Broadcasting', 'generate' => true],
                'event' => ['path' => 'Events', 'generate' => true],
                'listener' => ['path' => 'Listeners', 'generate' => true],
                'migration' => ['path' => 'Database/Migrations', 'generate' => true],
                'factory' => ['path' => 'Database/factories', 'generate' => true],
                'model' => ['path' => 'Entities', 'generate' => true],
                'observer' => ['path' => 'Observers', 'generate' => true],
                'repository' => ['path' => 'Repositories', 'generate' => true],
                'seeder' => ['path' => 'Database/Seeders', 'generate' => true],
                'controller' => ['path' => 'Http/Controllers', 'generate' => true],
                'filter' => ['path' => 'Http/Middleware', 'generate' => true],
                'request' => ['path' => 'Http/Requests', 'generate' => true],
                'provider' => ['path' => 'Providers', 'generate' => true],
                'lang' => ['path' => 'Resources/lang', 'generate' => true],
                'views' => ['path' => 'Resources/views', 'generate' => true],
                'policies' => ['path' => 'Policies', 'generate' => true],
                'rules' => ['path' => 'Rules', 'generate' => true],
                'test-feature' => ['path' => 'Tests/Feature', 'generate' => true],
                'test' => ['path' => 'Tests/Unit', 'generate' => true],
                'jobs' => ['path' => 'Jobs', 'generate' => true],
                'emails' => ['path' => 'Emails', 'generate' => true],
                'notifications' => ['path' => 'Notifications', 'generate' => true],
                'resource' => ['path' => 'Transformers', 'generate' => true],
                'component-view' => ['path' => 'Resources/views/components', 'generate' => true],
                'component-class' => ['path' => 'View/Component', 'generate' => true],
            ],
        ]);

        $app['config']->set('modules.composer-output', true);

        $app['config']->set('modules.commands', ConsoleServiceProvider::defaultCommands()->toArray());
    }

    protected function setUpDatabase()
    {
        $this->resetDatabase();
    }
}
