<?php

namespace Nwidart\Modules\Tests\Actuator;

use Nwidart\Actuator\Health\Status;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Actuator\ModulesHealthIndicator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Tests\BaseTestCase;

class ModulesHealthIndicatorTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->artisan('module:disable', ['module' => ['Shop']]);
    }

    protected function tearDown(): void
    {
        // Some tests remove a module folder behind the repository, so delete the folders directly.
        $this->app['files']->deleteDirectory($this->getModuleBasePath('Blog'));
        $this->app['files']->deleteDirectory($this->getModuleBasePath('Shop'));
        $this->app['files']->deleteDirectory($this->getModuleBasePath('Broken'));
        $this->app[RepositoryInterface::class]->resetModules();
        $this->app[ActivatorInterface::class]->reset();

        parent::tearDown();
    }

    public function test_it_is_up_and_lists_enabled_and_disabled_modules()
    {
        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => true],
                'Shop' => ['enabled' => false],
            ],
        ], $health->details);
    }

    public function test_it_is_down_when_an_enabled_module_is_not_on_disk()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Ghost', true);

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Down, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => true],
                'Shop' => ['enabled' => false],
                'Ghost' => ['enabled' => true, 'error' => 'Enabled in the status store, not found on disk.'],
            ],
        ], $health->details);
    }

    public function test_a_disabled_module_that_is_not_on_disk_does_not_change_the_status()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Ghost', false);

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertArrayNotHasKey('Ghost', $health->details['modules']);
    }

    public function test_it_matches_stored_names_case_insensitively_like_the_repository()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('blog', true);

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame(['Blog', 'Shop'], array_keys($health->details['modules']));
    }

    public function test_it_skips_the_missing_module_check_with_an_activator_that_is_not_a_file_activator()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Ghost', true);

        $activator = $this->onlyShopEnabledActivator();

        $health = (new ModulesHealthIndicator($this->app[RepositoryInterface::class], $activator))->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => false],
                'Shop' => ['enabled' => true],
            ],
        ], $health->details);
    }

    public function test_it_is_down_when_an_enabled_module_folder_is_removed_after_the_scan()
    {
        $repository = $this->app[RepositoryInterface::class];
        $this->assertCount(2, $repository->all());

        // A long-lived process (Octane, a queue worker) keeps the scanned modules.
        $this->app['files']->deleteDirectory($this->getModuleBasePath('Blog'));

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Down, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => true, 'error' => 'Enabled in the status store, not found on disk.'],
                'Shop' => ['enabled' => false],
            ],
        ], $health->details);
    }

    public function test_a_disabled_module_folder_removed_after_the_scan_is_left_out()
    {
        $this->assertCount(2, $this->app[RepositoryInterface::class]->all());

        $this->app['files']->deleteDirectory($this->getModuleBasePath('Shop'));

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame(['modules' => ['Blog' => ['enabled' => true]]], $health->details);
    }

    public function test_a_removed_module_folder_is_reported_with_an_activator_that_is_not_a_file_activator()
    {
        $this->assertCount(2, $this->app[RepositoryInterface::class]->all());
        $this->app['files']->deleteDirectory($this->getModuleBasePath('Blog'));
        $this->app['files']->deleteDirectory($this->getModuleBasePath('Shop'));

        $health = (new ModulesHealthIndicator($this->app[RepositoryInterface::class], $this->onlyShopEnabledActivator()))->health();

        $this->assertSame(Status::Down, $health->status);
        $this->assertSame([
            'modules' => [
                'Shop' => ['enabled' => true, 'error' => 'Enabled in the status store, not found on disk.'],
            ],
        ], $health->details);
    }

    public function test_it_is_down_and_lists_the_broken_modules_with_their_error()
    {
        $this->skipBrokenModules();

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Down, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => true, 'error' => 'Provider class [Modules\Blog\Providers\MissingServiceProvider] not found.'],
                'Shop' => ['enabled' => false],
                'Broken' => ['enabled' => true, 'error' => 'Error processing file: modules/Broken/module.json. Error: Syntax error'],
            ],
        ], $health->details);
    }

    public function test_a_broken_module_json_shows_the_stored_status_of_its_folder_name()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Broken', false);
        $this->skipBrokenModules();

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Down, $health->status);
        $this->assertFalse($health->details['modules']['Broken']['enabled']);
        $this->assertArrayHasKey('error', $health->details['modules']['Broken']);
    }

    public function test_file_activator_returns_the_stored_statuses()
    {
        $activator = $this->app[ActivatorInterface::class];
        $this->assertInstanceOf(FileActivator::class, $activator);

        $activator->setActiveByName('Ghost', true);

        $this->assertSame(['Blog' => true, 'Shop' => false, 'Ghost' => true], $activator->getStatuses());
    }

    /**
     * Boot with 'skip_broken_modules': Blog lists a provider class that does not exist, Broken has an invalid module.json.
     */
    private function skipBrokenModules(): void
    {
        config(['modules.skip_broken_modules' => true]);
        $repository = $this->app[RepositoryInterface::class];
        $repository->find('Blog')->json()->set('providers', ['Modules\Blog\Providers\MissingServiceProvider'])->save();
        $this->app['files']->ensureDirectoryExists($this->getModuleBasePath('Broken'));
        $this->app['files']->put($this->getModuleBasePath('Broken').'/module.json', '{ broken');
        $repository->resetModules();

        $this->app->forgetInstance(ModuleManifest::class);
        $this->app->make(ModuleManifest::class)->getProviders();
    }

    /**
     * An activator that is not a FileActivator: only Shop is enabled.
     */
    private function onlyShopEnabledActivator(): ActivatorInterface
    {
        return new class implements ActivatorInterface
        {
            public function enable(Module $module): void {}

            public function disable(Module $module): void {}

            public function hasStatus(Module|string $module, bool $status): bool
            {
                $name = $module instanceof Module ? $module->getName() : $module;

                return ($name === 'Shop') === $status;
            }

            public function setActive(Module $module, bool $active): void {}

            public function setActiveByName(string $name, bool $active): void {}

            public function delete(Module $module): void {}

            public function reset(): void {}
        };
    }
}
