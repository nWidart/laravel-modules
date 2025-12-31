<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\LaravelModulesServiceProvider;

class LaravelModulesServiceProviderTest extends BaseTestCase
{
    private ActivatorInterface $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->activator->reset();
        $this->artisan('module:delete', ['--all' => true, '--force' => true]);
        parent::tearDown();
    }

    public function test_it_binds_modules_key_to_repository_class()
    {
        $this->assertInstanceOf(RepositoryInterface::class, app(RepositoryInterface::class));
        $this->assertInstanceOf(RepositoryInterface::class, app('modules'));
    }

    public function test_it_binds_activator_to_activator_class()
    {
        $this->assertInstanceOf(ActivatorInterface::class, app(ActivatorInterface::class));
    }

    public function test_it_throws_exception_if_config_is_invalid()
    {
        $this->expectException(InvalidActivatorClass::class);

        $this->app['config']->set('modules.activators.file', ['class' => null]);

        app()->forgetInstance(ActivatorInterface::class);

        $this->assertInstanceOf(ActivatorInterface::class, app(ActivatorInterface::class));
    }

    public function test_get_modules_for_migration_returns_all_enabled_modules_when_no_filters()
    {
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Blog'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Shop'));

        $this->app['config']->set('modules.auto-discover.include_modules', []);
        $this->app['config']->set('modules.auto-discover.exclude_modules', []);

        $provider = new LaravelModulesServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getModulesForMigration');
        $method->setAccessible(true);

        $modules = $method->invoke($provider);

        $this->assertCount(2, $modules);
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Blog'));
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Shop'));
    }

    public function test_get_modules_for_migration_filters_by_include_modules()
    {
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->createModule('Admin');
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Blog'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Shop'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Admin'));

        $this->app['config']->set('modules.auto-discover.include_modules', ['Blog', 'Shop']);
        $this->app['config']->set('modules.auto-discover.exclude_modules', []);

        $provider = new LaravelModulesServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getModulesForMigration');
        $method->setAccessible(true);

        $modules = $method->invoke($provider);

        $this->assertCount(2, $modules);
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Blog'));
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Shop'));
        $this->assertFalse($modules->contains(fn ($m) => $m->getName() === 'Admin'));
    }

    public function test_get_modules_for_migration_filters_by_exclude_modules()
    {
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->createModule('Admin');
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Blog'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Shop'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Admin'));

        $this->app['config']->set('modules.auto-discover.include_modules', []);
        $this->app['config']->set('modules.auto-discover.exclude_modules', ['Admin']);

        $provider = new LaravelModulesServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getModulesForMigration');
        $method->setAccessible(true);

        $modules = $method->invoke($provider);

        $this->assertCount(2, $modules);
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Blog'));
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Shop'));
        $this->assertFalse($modules->contains(fn ($m) => $m->getName() === 'Admin'));
    }

    public function test_get_modules_for_migration_include_takes_precedence_over_exclude()
    {
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->createModule('Admin');
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Blog'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Shop'));
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Admin'));

        // Both include and exclude defined - include should take precedence
        $this->app['config']->set('modules.auto-discover.include_modules', ['Blog']);
        $this->app['config']->set('modules.auto-discover.exclude_modules', ['Blog', 'Shop']);

        $provider = new LaravelModulesServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getModulesForMigration');
        $method->setAccessible(true);

        $modules = $method->invoke($provider);

        // Only Blog should be included (include takes precedence, exclude is ignored)
        $this->assertCount(1, $modules);
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Blog'));
    }

    public function test_get_modules_for_migration_only_includes_enabled_modules()
    {
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->activator->enable($this->app[RepositoryInterface::class]->find('Blog'));
        // Explicitly disable Shop
        $this->activator->disable($this->app[RepositoryInterface::class]->find('Shop'));

        $this->app['config']->set('modules.auto-discover.include_modules', ['Blog', 'Shop']);
        $this->app['config']->set('modules.auto-discover.exclude_modules', []);

        $provider = new LaravelModulesServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getModulesForMigration');
        $method->setAccessible(true);

        $modules = $method->invoke($provider);

        // Only Blog should be returned (Shop is disabled)
        $this->assertCount(1, $modules);
        $this->assertTrue($modules->contains(fn ($m) => $m->getName() === 'Blog'));
    }
}
