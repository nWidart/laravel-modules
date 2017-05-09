<?php

namespace Nwidart\Modules\tests;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;
use Nwidart\Modules\Repository;

class RepositoryTest extends BaseTestCase
{
    /**
     * @var Repository
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();
        $this->repository = new Repository($this->app);
    }

    /** @test */
    public function it_adds_location_to_paths()
    {
        $this->repository->addLocation('some/path');
        $this->repository->addPath('some/other/path');

        $paths = $this->repository->getPaths();
        $this->assertCount(2, $paths);
        $this->assertEquals('some/path', $paths[0]);
        $this->assertEquals('some/other/path', $paths[1]);
    }

    /** @test */
    public function it_returns_a_collection()
    {
        $this->repository->addLocation(__DIR__ . '/stubs');

        $this->assertInstanceOf(Collection::class, $this->repository->toCollection());
        $this->assertInstanceOf(Collection::class, $this->repository->collections());
    }

    /** @test */
    public function it_returns_all_enabled_modules()
    {
        $this->repository->addLocation(__DIR__ . '/stubs');

        $this->assertCount(1, $this->repository->getByStatus(1));
        $this->assertCount(1, $this->repository->enabled());
    }

    /** @test */
    public function it_returns_all_disabled_modules()
    {
        $this->repository->addLocation(__DIR__ . '/stubs');

        $this->assertCount(0, $this->repository->getByStatus(0));
        $this->assertCount(0, $this->repository->disabled());
    }

    /** @test */
    public function it_counts_all_modules()
    {
        $this->repository->addLocation(__DIR__ . '/stubs');

        $this->assertEquals(1, $this->repository->count());
    }

    /** @test */
    public function it_finds_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');

        $this->assertInstanceOf(Module::class, $this->repository->find('recipe'));
        $this->assertInstanceOf(Module::class, $this->repository->get('recipe'));
    }

    /** @test */
    public function it_finds_a_module_by_alias()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');
        $this->repository->addLocation(__DIR__ . '/stubs/Requirement');

        $this->assertInstanceOf(Module::class, $this->repository->findByAlias('recipe'));
        $this->assertInstanceOf(Module::class, $this->repository->findByAlias('required_module'));
    }

    /** @test */
    public function it_find_or_fail_throws_exception_if_module_not_found()
    {
        $this->expectException(ModuleNotFoundException::class);

        $this->repository->findOrFail('something');
    }

    /** @test */
    public function it_finds_the_module_asset_path()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');
        $assetPath = $this->repository->assetPath('recipe');

        $this->assertEquals(public_path('modules/recipe'), $assetPath);
    }

    /** @test */
    public function it_gets_the_used_storage_path()
    {
        $path = $this->repository->getUsedStoragePath();

        $this->assertEquals(storage_path('app/modules/modules.used'), $path);
    }

    /** @test */
    public function it_sets_used_module()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');

        $this->repository->setUsed('Recipe');

        $this->assertEquals('Recipe', $this->repository->getUsed());
        $this->assertEquals('Recipe', $this->repository->getUsedNow());
    }

    /** @test */
    public function it_returns_laravel_filesystem()
    {
        $this->assertInstanceOf(Filesystem::class, $this->repository->getFiles());
    }

    /** @test */
    public function it_gets_the_assets_path()
    {
        $this->assertEquals(public_path('modules'), $this->repository->getAssetsPath());
    }

    /** @test */
    public function it_gets_a_specific_module_asset()
    {
        $path = $this->repository->asset('recipe:test.js');

        $this->assertEquals('//localhost/modules/recipe/test.js', $path);
    }

    /** @test */
    public function it_can_detect_if_module_is_active()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');

        $this->assertTrue($this->repository->active('Recipe'));
    }

    /** @test */
    public function it_can_detect_if_module_is_inactive()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');

        $this->assertFalse($this->repository->notActive('Recipe'));
    }

    /** @test */
    public function it_can_get_and_set_the_stubs_path()
    {
        $this->repository->setStubPath('some/stub/path');

        $this->assertEquals('some/stub/path', $this->repository->getStubPath());
    }

    /** @test */
    public function it_gets_the_configured_stubs_path_if_enabled()
    {
        $this->app['config']->set('modules.stubs.enabled', true);

        $this->assertEquals(base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'), $this->repository->getStubPath());
    }

    /** @test */
    public function it_returns_default_stub_path()
    {
        $this->assertNull($this->repository->getStubPath());
    }

    /** @test */
    public function it_can_disabled_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');

        $this->repository->disable('Recipe');

        $this->assertTrue($this->repository->notActive('Recipe'));
    }

    /** @test */
    public function it_can_enable_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');

        $this->repository->enable('Recipe');

        $this->assertTrue($this->repository->active('Recipe'));
    }

    /** @test */
    public function it_can_delete_a_module()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $this->repository->delete('Blog');

        $this->assertFalse(is_dir(base_path('modules/Blog')));
    }

    /** @test */
    public function it_can_find_all_requirements_of_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');
        $this->repository->addLocation(__DIR__ . '/stubs/Requirement');

        $requirements = $this->repository->findRequirements('Recipe');

        $this->assertCount(1, $requirements);
        $this->assertInstanceOf(Module::class, $requirements[0]);
    }

    /** @test */
    public function it_can_register_macros()
    {
        Module::macro('registeredMacro', function () {});

        $this->assertTrue(Module::hasMacro('registeredMacro'));
    }

    /** @test */
    public function it_does_not_have_unregistered_macros()
    {
        $this->assertFalse(Module::hasMacro('unregisteredMacro'));
    }

    /** @test */
    public function it_calls_macros_on_modules()
    {
        Module::macro('getReverseName', function () {
            return strrev($this->getLowerName());
        });

        $this->repository->addLocation(__DIR__ . '/stubs/Recipe');
        $module = $this->repository->find('recipe');

        $this->assertEquals('epicer', $module->getReverseName());
    }
}
