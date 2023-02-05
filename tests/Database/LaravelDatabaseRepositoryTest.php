<?php

namespace Nwidart\Modules\Tests\Database;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Exceptions\InvalidAssetPath;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Laravel\LaravelDatabaseRepository;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class LaravelDatabaseRepositoryTest extends BaseTestCase
{
    use DatabaseMigrations;

    /**
     * @var LaravelDatabaseRepository
     */
    private $repository;

    /**
     * @var LaravelFileRepository
     */
    private $fileRepository;

    /**
     * @var ActivatorInterface
     */
    private $activator;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new LaravelDatabaseRepository($this->app);
        $this->fileRepository = new LaravelFileRepository($this->app);
        $this->activator = $this->app[ActivatorInterface::class];
        app()->bind(RepositoryInterface::class, LaravelDatabaseRepository::class);
    }

    public function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
        app()->bind(RepositoryInterface::class, LaravelFileRepository::class);

        // Support for windows command.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = 'rd /s /q ';
        } else {
            $command = 'rm -rf ';
        }
        system($command . escapeshellarg(base_path('modules')));
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('modules.database_management.enabled', true);
    }

    /** @test */
    public function it_can_create_a_module()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1]);
        $this->assertTrue(is_dir(base_path("modules/{$moduleName}")));
    }

    /** @test */
    public function it_can_create_a_api_module()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1], true, true, false);
        $this->assertTrue(is_dir(base_path("modules/{$moduleName}")));
    }

    /** @test */
    public function it_can_create_a_plain_module()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1], true, false, true);
        $this->assertTrue(is_dir(base_path("modules/{$moduleName}")));
    }

    /** @test */
    public function it_can_create_a_web_module()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1], true, false, false);
        $this->assertTrue(is_dir(base_path("modules/{$moduleName}")));
    }

    /** @test */
    public function it_can_create_a_module_fail_if_exists()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1]);
        $this->expectExceptionMessage("Module [{$moduleName}] already exist!");
        $this->repository->create(['name' => $moduleName, 'is_active' => 1], false);
    }

    /** @test */
    public function it_can_create_a_module_fail_by_artisan()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1]);
        Artisan::call('module:make', [
            'name' => [$moduleName],
        ]);
        $this->assertStringContainsStringIgnoringCase("Module [{$moduleName}] already exist!", Artisan::output());
    }

    /** @test */
    public function it_can_create_a_module_by_artisan()
    {
        $moduleName = $this->generateModuleName();
        $output = Artisan::call('module:make', [
            'name' => [$moduleName],
        ]);
        $this->assertTrue(is_dir(base_path("modules/{$moduleName}")));
        $this->assertTrue($this->repository->has($moduleName));
        $this->assertEquals(1, $output);
    }

    /** @test */
    public function it_can_create_a_module_force_if_exists()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1]);
        // Force re-create.
        $module = $this->repository->create(['name' => $moduleName, 'is_active' => 1], true);
        $this->assertTrue(is_dir(base_path("modules/{$moduleName}")));
        $this->assertEquals($moduleName, $module->getName());
    }

    /** @test */
    public function it_returns_a_collection()
    {
        $this->createModule('Recipe');
        $this->assertInstanceOf(Collection::class, $this->repository->toCollection());
        $this->assertInstanceOf(Collection::class, $this->repository->collections());
        $this->assertEquals(1, $this->repository->toCollection()->count());
    }

    /** @test */
    public function it_returns_all_enabled_modules()
    {
        $this->createModule('Recipe', 1);
        $this->createModule('Requirement', 0);

        $this->assertCount(1, $this->repository->getByStatus(true));
        $this->assertCount(1, $this->repository->allEnabled());
    }

    /** @test */
    public function it_returns_all_disabled_modules()
    {
        $this->createModule('Recipe', 0);
        $this->createModule('Requirement', 0);

        $this->assertCount(2, $this->repository->getByStatus(false));
        $this->assertCount(2, $this->repository->allDisabled());
    }

    /** @test */
    public function it_counts_all_modules()
    {
        $this->createModule('Recipe', 1);
        $this->createModule('Requirement', 0);
        $this->assertEquals(2, $this->repository->count());
    }

    /** @test */
    public function it_finds_a_module()
    {
        $this->createModule('Recipe', 1);
        $this->assertInstanceOf(Module::class, $this->repository->find('Recipe'));
    }

    /** @test */
    public function it_finds_a_not_found_module()
    {
        $this->assertNull($this->repository->find('Recipe'));
    }

    /** @test */
    public function it_finds_a_module_by_alias()
    {
        if (!method_exists($this->repository, 'findByAlias')) {
            $this->markTestSkipped();
        }
        $this->createModule('Recipe', 1);
        $this->assertInstanceOf(Module::class, $this->repository->findByAlias('recipe'));
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
        $this->repository->addLocation(__DIR__ . '/stubs/valid/Recipe');
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
        $this->createModule('Recipe', 1);
        $this->repository->setUsed('Recipe');
        $this->assertEquals('Recipe', $this->repository->getUsedNow());
    }

    /** @test */
    public function it_sets_used_module_not_found()
    {
        $this->expectException(ModuleNotFoundException::class);
        $this->repository->setUsed('something');
    }

    /** @test */
    public function it_sets_forget_used_module()
    {
        $this->createModule('Recipe', 1);
        $this->repository->setUsed('Recipe');
        $this->repository->forgetUsed();
        $this->assertEmpty($this->repository->getUsedNow());
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
    public function it_throws_exception_if_module_is_omitted()
    {
        $this->expectException(InvalidAssetPath::class);
        $this->expectExceptionMessage('Module name was not specified in asset [test.js].');

        $this->repository->asset('test.js');
    }

    /** @test */
    public function it_can_detect_if_module_is_active()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/valid');
        $this->createModule('Recipe', 0);
        $this->assertFalse($this->repository->isEnabled('Recipe'));

        $this->repository->enable('Recipe');

        $this->assertTrue($this->repository->isEnabled('Recipe'));
    }

    /** @test */
    public function it_can_detect_if_module_is_inactive()
    {
        $this->repository->addLocation(__DIR__ . '/stubs/valid');
        $this->createModule('Recipe', 1);
        $this->assertFalse($this->repository->isDisabled('Recipe'));

        $this->repository->disable('Recipe');

        $this->assertTrue($this->repository->isDisabled('Recipe'));
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
    public function it_can_delete_a_module()
    {
        $moduleName = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName, 'is_active' => 1]);
        $this->repository->delete($moduleName);

        $this->assertFalse(is_dir(base_path("modules/{$moduleName}")));
    }

    /** @test */
    public function it_can_find_all_requirements_of_a_module()
    {
        if (!method_exists($this->repository, 'findRequirements')) {
            $this->markTestSkipped();
        }

        $module = $this->createModule('Recipe');

        $moduleName1 = $this->generateModuleName();
        $moduleName2 = $this->generateModuleName();
        $this->repository->create(['name' => $moduleName1, 'is_active' => 1]);
        $this->repository->create(['name' => $moduleName2, 'is_active' => 1]);

        $requires = [
            Str::lower($moduleName1),
            Str::lower($moduleName2),
        ];
        $module->requires = $requires;
        $module->save();

        $requirements = $this->repository->findRequirements('Recipe');

        $this->assertCount(count($requires), $requirements);
        $this->assertInstanceOf(Module::class, $requirements[0]);
        $this->assertEquals($moduleName1, $requirements[0]->getName());
        $this->assertEquals($moduleName2, $requirements[1]->getName());
    }

    /** @test */
    public function it_can_register_macros()
    {
        Module::macro('registeredMacro', function () {
        });

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
            return strrev(Str::lower($this->getName()));
        });

        $this->createModule('Recipe');
        $module = $this->repository->find('Recipe');

        $this->assertEquals('epicer', $module->getReverseName());
    }

    /** @test */
    public function it_returns_all_modules()
    {
        $this->createModule('Recipe');
        $this->createModule('Requirement');
        $this->createModule('Module-not-exists');
        $this->assertCount(2, $this->repository->all());
    }

    /** @test */
    public function it_returns_all_modules_using_cache()
    {
        config()->set('modules.cache.enabled', true);
        config()->set('modules.cache.key', 'laravel-modules');
        config()->set('modules.cache.lifetime', 3000);
        $moduleName = 'Recipe';
        $updatedDescription = 'New description';
        $moduleEntity = $this->createModule($moduleName);

        // Cache first.
        $this->repository->all();

        // Update db.
        DB::table('modules')
            ->where('name', $moduleName)
            ->update(['description' => $updatedDescription]);

        $modules = $this->repository->all();
        $this->assertCount(1, $modules);
        $this->assertArrayHasKey($moduleName, $modules);
        // Should get cache, not updated description.
        $this->assertNotEquals($updatedDescription, $modules[$moduleName]->get('description'));
        $this->assertEquals($moduleEntity->description, $modules[$moduleName]->get('description'));
        config()->set('modules.cache.enabled', false);
    }

    /** @test */
    public function it_returns_all_modules_without_using_cache()
    {
        config()->set('modules.cache.enabled', false);
        $moduleName = 'Recipe';
        $updatedDescription = 'New description';
        $this->createModule($moduleName);

        // Try to get first.
        $this->repository->all();

        // Update new module name.
        DB::table('modules')
            ->where('name', $moduleName)
            ->update(['description' => $updatedDescription]);

        $modules = $this->repository->all();
        $this->assertCount(1, $modules);
        $this->assertArrayHasKey($moduleName, $modules);
        // Should get new module description.
        $this->assertEquals($updatedDescription, $modules[$moduleName]->get('description'));
    }

    /** @test */
    public function it_returns_empty_array_if_database_not_exists()
    {
        Schema::dropIfExists('modules');
        $this->assertEmpty($this->repository->all());
    }

    /** @test */
    public function it_can_migrate_all_local_modules_to_database_management()
    {
        $this->fileRepository->addLocation(__DIR__ . '/../stubs/valid');
        # Assume we have 2 local modules and we need to add it into database.
        $this->assertEquals(2, $this->fileRepository->count());
        $this->assertEquals(0, $this->repository->count());

        # Add the local path.
        $this->repository->addLocation(__DIR__ . '/../stubs/valid');
        # Start to migrate.
        $this->repository->migrateFileToDatabase();
        $this->assertEquals(2, $this->repository->count());
    }

    /** @test */
    public function it_can_migrate_all_local_modules_to_database_management_update_higher_version()
    {
        $this->fileRepository->addLocation(__DIR__ . '/../stubs/valid');
        $testVersion = '0.0.1';
        $this->createModule('Recipe', true, $testVersion);
        # Assume we have 2 local modules and we need to add it into database.
        $this->assertEquals(2, $this->fileRepository->count());
        $this->assertEquals(1, $this->repository->count());
        $this->assertEquals($testVersion, $this->repository->find('Recipe')->getVersion());

        # Add the local path.
        $this->repository->addLocation(__DIR__ . '/../stubs/valid');
        # Start to migrate.
        $this->repository->migrateFileToDatabase();
        $this->assertEquals(2, $this->repository->count());
        $currentVersion = $this->repository->find('Recipe')->getVersion();
        $this->assertTrue(version_compare($testVersion, $currentVersion, '<'));
    }

    /** @test */
    public function it_can_get_extra_data_module()
    {
        $this->fileRepository->addLocation(__DIR__ . '/../stubs/valid');
        $test_1 = 'Test string 123';
        $test_2 = 9999;
        $this->createModule('Recipe', true);
        $module = $this->repository->findOrFail('Recipe');
        $module->setAttributes(array_merge($module->getAttributes(), [
            'test_1' => $test_1,
            'test_2' => $test_2,
        ]));
        $this->assertEquals('Recipe', $module->get('name'));
        $this->assertEquals($test_1, $module->get('test_1'));
        $this->assertEquals($test_2, $module->get('test_2'));
    }

    /** @test */
    public function it_can_be_order()
    {
        $this->fileRepository->addLocation(__DIR__ . '/../stubs/valid');
        $this->createModule('Recipe', true, '1.0.0', 1);
        $this->createModule('Requirement', true, '1.0.0', 2);

        $modules = $this->repository->getOrdered();
        $this->assertCount(2, $modules);
        $moduleNames = array_keys($modules);
        $this->assertEquals('Recipe', $moduleNames[0]);
        $this->assertEquals('Requirement', $moduleNames[1]);
    }

    /** @test */
    public function it_can_be_order_desc()
    {
        $this->fileRepository->addLocation(__DIR__ . '/../stubs/valid');
        $this->createModule('Recipe', true, '1.0.0', 1);
        $this->createModule('Requirement', true, '1.0.0', 2);

        $modules = $this->repository->getOrdered('desc');
        $this->assertCount(2, $modules);
        $moduleNames = array_keys($modules);
        $this->assertEquals('Requirement', $moduleNames[0]);
        $this->assertEquals('Recipe', $moduleNames[1]);
    }

//    /** @test */
//    public function it_can_be_update_by_artisan()
//    {
//        $this->fileRepository->addLocation(__DIR__ . '/../stubs/valid');
//        $oldVersion = '0.1';
//        $module = $this->createModule('Recipe', true, $oldVersion);
//        Artisan::call('module:update', [
//            'module' => $module->name,
//        ]);
//        $this->assertEquals('0.1', $module->refresh()->version);
//    }

    private function createModule($moduleName, $isActive = 1, $version = '1.0.0', $priority = 0): ModuleEntity
    {
        $moduleEntity = new ModuleEntity();
        $moduleEntity->name = $moduleName;
        $moduleEntity->alias = Str::lower($moduleName);
        $moduleEntity->path = __DIR__ . "/../stubs/valid/{$moduleName}";
        $moduleEntity->is_active = $isActive;
        $moduleEntity->version = $version;
        $moduleEntity->priority = $priority;
        $moduleEntity->save();

        return $moduleEntity;
    }

    protected static $num = 1;

    private function generateModuleName(): string
    {
        return 'Blog' . time() . ++self::$num;
    }
}
