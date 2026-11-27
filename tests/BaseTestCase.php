<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Process\Factory;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeComposerDumpAutoload();

        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }
        // $this->setUpDatabase();
    }

    /**
     * Fake `composer dump-autoload`, which module:make runs in base_path(), the Testbench skeleton.
     * A real dump there is slow, and it can overwrite the package's own autoloader: Testbench's
     * CLI links the skeleton's vendor/ to the package's vendor/ and can leave that link behind.
     *
     * Process::fake() would not do: it records every process (so assertNothingRan() would see the
     * dumps of a setUp()'s createModule()), and the first matching pattern wins (so it would shadow
     * a test's own Process::fake(['*dump-autoload*' => ...])). This factory adds the dump fake after
     * the test's fakes, without recording. Other commands still run for real.
     * Override this method with an empty body in a test that needs a real dump.
     */
    protected function fakeComposerDumpAutoload(): void
    {
        Process::swap(new class extends Factory
        {
            #[\Override]
            public function newPendingProcess()
            {
                return (new PendingProcess($this))->withFakeHandlers($this->fakeHandlers + [
                    'composer dump-autoload*' => fn () => new FakeProcessResult,
                ]);
            }
        });
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
     * @param  Application  $app
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

    protected function createModule(string $moduleName = 'Blog'): int
    {
        return $this->artisan('module:make', ['name' => [$moduleName]]);
    }

    protected function getModuleAppPath(string $moduleName = 'Blog'): string
    {
        return base_path("modules/$moduleName/").rtrim(config('modules.paths.app_folder'), '/');
    }

    protected function getModuleBasePath(string $moduleName = 'Blog'): string
    {
        return base_path("modules/$moduleName");
    }
}
