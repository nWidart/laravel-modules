<?php

namespace Nwidart\Modules\tests;

use Nwidart\Modules\LaravelModulesServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

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
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', array(
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ));
        $app['config']->set('modules.paths.modules', base_path('modules'));
        $app['config']->set('modules.paths', [
            'modules' => base_path('modules'),
            'assets' => public_path('modules'),
            'migration' => base_path('database/migrations'),
            'generator' => [
                'assets' => 'Assets',
                'config' => 'Config',
                'command' => 'Console',
                'event' => 'Events',
                'listener' => 'Listeners',
                'migration' => 'Database/Migrations',
                'model' => 'Entities',
                'repository' => 'Repositories',
                'seeder' => 'Database/Seeders',
                'controller' => 'Http/Controllers',
                'filter' => 'Http/Middleware',
                'request' => 'Http/Requests',
                'provider' => 'Providers',
                'lang' => 'Resources/lang',
                'views' => 'Resources/views',
                'test' => 'Tests',
                'jobs' => 'Jobs',
                'emails' => 'Emails',
            ],
        ]);
    }

    protected function setUpDatabase()
    {
        $this->resetDatabase();
    }
}
