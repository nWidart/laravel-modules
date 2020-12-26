<?php
declare(strict_types=1);

namespace Nwidart\Modules\Tests\Database;

use Illuminate\Support\Str;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Laravel\LaravelEloquentRepository;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;

class LaravelEloquentRepositoryTest extends BaseTestCase
{
    /**
     * @var LaravelEloquentRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = app(LaravelEloquentRepository::class);
    }

    /** @test */
    public function it_returns_all_modules(): void
    {
        $this->createModule('Recipe');

        $this->assertCount(1, $this->repository->all());
        $this->assertCount(1, $this->repository->scan());
    }

    /** @test */
    public function it_returns_a_collection_of_module_instances()
    {
        $this->createModule('Recipe');
        $this->createModule('Requirement');
        $this->createModule('DisabledModule',0);

        $this->assertInstanceOf(Collection::class, $this->repository->toCollection());
        $this->assertCount(3,  $this->repository->toCollection());
    }

    /** @test */
    public function it_returns_all_enabled_modules()
    {
        $this->createModule('Recipe');
        $moduleTwo = $this->createModule('Requirement');
        $moduleTwo->is_active = 0;
        $moduleTwo->save();

        $this->assertCount(1,  $this->repository->allEnabled());
    }

    /** @test */
    public function it_returns_all_disabled_modules()
    {
        $this->createModule('Recipe');
        $moduleTwo = $this->createModule('Requirement');
        $moduleTwo->is_active = 0;
        $moduleTwo->save();

        $this->assertCount(1,  $this->repository->allDisabled());
    }

    /** @test */
    public function it_counts_all_modules()
    {
        $this->createModule('Recipe');
        $this->createModule('Requirement');

        $this->assertEquals(2, $this->repository->count());
    }

    /** @test */
    public function it_returns_ordered_collection_of_enabled_modules_in_ascending_order()
    {
        $moduleOne = $this->createModule('Recipe');
        $moduleOne->order = 1;
        $moduleOne->save();
        $moduleTwo = $this->createModule('Requirement');
        $moduleTwo->order = 10;
        $moduleTwo->save();
        $moduleThree = $this->createModule('DisabledModule',0);
        $moduleThree->order = 5;
        $moduleThree->save();

        $modules = $this->repository->getOrdered('asc');
        $this->assertEquals('Recipe', $modules[0]['name']);
        $this->assertEquals('DisabledModule', $modules[1]['name']);
        $this->assertEquals('Requirement', $modules[2]['name']);
    }

    /** @test */
    public function it_returns_ordered_collection_of_enabled_modules_in_descending_order()
    {
        $moduleOne = $this->createModule('Recipe');
        $moduleOne->order = 1;
        $moduleOne->save();
        $moduleTwo = $this->createModule('Requirement');
        $moduleTwo->order = 10;
        $moduleTwo->save();
        $moduleThree = $this->createModule('DisabledModule',0);
        $moduleThree->order = 5;
        $moduleThree->save();

        $modules = $this->repository->getOrdered('desc');
        $this->assertEquals('Requirement', $modules[0]['name']);
        $this->assertEquals('DisabledModule', $modules[1]['name']);
        $this->assertEquals('Recipe', $modules[2]['name']);
    }

    /** @test */
    public function it_gets_module_by_given_status()
    {
        $module = $this->createModule('Requirement');
        $module->is_active = 0;
        $module->save();

        $this->assertCount(1,  $this->repository->getByStatus(0));
        $this->assertCount(0,  $this->repository->getByStatus(1));
    }

    /** @test */
    public function it_can_find_module_by_name()
    {
        $this->createModule('Recipe');
        $this->createModule('Requirement');

        $this->assertEquals('Recipe', $this->repository->find('Recipe')->name);
    }

    /** @test */
    public function it_returns_null_if_module_was_not_found()
    {
        $this->assertNull($this->repository->find('Unknown'));
    }

    /** @test */
    public function it_throws_exception_if_module_was_not_found()
    {
        $this->expectException(ModuleNotFoundException::class);

        $this->repository->findOrFail('Unknown');
    }

    /** @test */
    public function it_returns_the_module_path()
    {
        $this->createModule('Recipe');

        $this->assertEquals(
            __DIR__ . '/../stubs/valid/Recipe',
            $this->repository->getModulePath('Recipe')
        );
    }

    private function createModule($moduleName,$isActive = 1): ModuleEntity
    {
        $moduleEntity = new ModuleEntity();
        $moduleEntity->name = $moduleName;
        $moduleEntity->alias = Str::lower($moduleName);
        $moduleEntity->path = __DIR__ . "/../stubs/valid/{$moduleName}";
        $moduleEntity->is_active = $isActive;
        $moduleEntity->save();

        return $moduleEntity;
    }
}
