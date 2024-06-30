<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Exceptions\InvalidAssetPath;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Module;

class LaravelFileRepositoryTest extends BaseTestCase
{
    /**
     * @var LaravelFileRepository
     */
    private $repository;

    /**
     * @var ActivatorInterface
     */
    private $activator;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new LaravelFileRepository($this->app);
        $this->activator = $this->app[ActivatorInterface::class];
    }

    public function tearDown(): void
    {
        $this->activator->reset();
        $this->artisan('module:delete', ['--all' => true, '--force' => true]);
        parent::tearDown();
    }

    public function test_it_adds_location_to_paths()
    {
        $this->repository->addLocation('some/path');

        $paths = $this->repository->getPaths();
        $this->assertCount(1, $paths);
        $this->assertEquals('some/path', $paths[0]);
    }

    public function test_it_returns_a_collection()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->assertInstanceOf(Collection::class, $this->repository->toCollection());
        $this->assertInstanceOf(Collection::class, $this->repository->collections());
    }

    public function test_it_returns_all_enabled_modules()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->assertCount(0, $this->repository->getByStatus(true));
        $this->assertCount(0, $this->repository->allEnabled());
    }

    public function test_it_returns_all_disabled_modules()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->assertCount(2, $this->repository->getByStatus(false));
        $this->assertCount(2, $this->repository->allDisabled());
    }

    public function test_it_counts_all_modules()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->assertEquals(2, $this->repository->count());
    }

    public function test_it_finds_a_module()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->assertInstanceOf(Module::class, $this->repository->find('recipe'));
    }

    public function test_it_find_or_fail_throws_exception_if_module_not_found()
    {
        $this->expectException(ModuleNotFoundException::class);

        $this->repository->findOrFail('something');
    }

    public function test_it_finds_the_module_asset_path()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid/Recipe');
        $assetPath = $this->repository->assetPath('recipe');

        $this->assertEquals(public_path('modules/recipe'), $assetPath);
    }

    public function test_it_gets_the_used_storage_path()
    {
        $path = $this->repository->getUsedStoragePath();

        $this->assertEquals(storage_path('app/modules/modules.used'), $path);
    }

    public function test_it_sets_used_module()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->repository->setUsed('Recipe');

        $this->assertEquals('Recipe', $this->repository->getUsedNow());
    }

    public function test_it_returns_laravel_filesystem()
    {
        $this->assertInstanceOf(Filesystem::class, $this->repository->getFiles());
    }

    public function test_it_gets_the_assets_path()
    {
        $this->assertEquals(public_path('modules'), $this->repository->getAssetsPath());
    }

    public function test_it_gets_a_specific_module_asset()
    {
        $path = $this->repository->asset('recipe:test.js');

        $this->assertEquals('//localhost/modules/recipe/test.js', $path);
    }

    public function test_it_throws_exception_if_module_is_omitted()
    {
        $this->expectException(InvalidAssetPath::class);
        $this->expectExceptionMessage('Module name was not specified in asset [test.js].');

        $this->repository->asset('test.js');
    }

    public function test_it_can_detect_if_module_is_active()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->repository->enable('Recipe');

        $this->assertTrue($this->repository->isEnabled('Recipe'));
    }

    public function test_it_can_detect_if_module_is_inactive()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->repository->isDisabled('Recipe');

        $this->assertTrue($this->repository->isDisabled('Recipe'));
    }

    public function test_it_can_get_and_set_the_stubs_path()
    {
        $this->repository->setStubPath('some/stub/path');

        $this->assertEquals('some/stub/path', $this->repository->getStubPath());
    }

    public function test_it_gets_the_configured_stubs_path_if_enabled()
    {
        $this->app['config']->set('modules.stubs.enabled', true);

        $this->assertEquals(base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'), $this->repository->getStubPath());
    }

    public function test_it_returns_default_stub_path()
    {
        $this->assertNull($this->repository->getStubPath());
    }

    public function test_it_can_disabled_a_module()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->repository->disable('Recipe');

        $this->assertTrue($this->repository->isDisabled('Recipe'));
    }

    public function test_it_can_enable_a_module()
    {
        $this->repository->addLocation(__DIR__.'/stubs/valid');

        $this->repository->enable('Recipe');

        $this->assertTrue($this->repository->isEnabled('Recipe'));
    }

    public function test_it_can_delete_a_module()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $this->repository->delete('Blog');

        $this->assertFalse(is_dir(base_path('modules/Blog')));
    }

    public function test_it_can_register_macros()
    {
        Module::macro('registeredMacro', function () {
        });

        $this->assertTrue(Module::hasMacro('registeredMacro'));
    }

    public function test_it_does_not_have_unregistered_macros()
    {
        $this->assertFalse(Module::hasMacro('unregisteredMacro'));
    }

    public function test_it_calls_macros_on_modules()
    {
        Module::macro('getReverseName', function () {
            return strrev($this->getLowerName());
        });

        $this->repository->addLocation(__DIR__.'/stubs/valid');
        $module = $this->repository->find('recipe');

        $this->assertEquals('epicer', $module->getReverseName());
    }
}
