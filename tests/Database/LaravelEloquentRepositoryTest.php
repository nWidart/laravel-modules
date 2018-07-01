<?php
declare(strict_types=1);

namespace Nwidart\Modules\Tests\Database;

use Nwidart\Modules\Collection;
use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Laravel\LaravelEloquentRepository;
use Nwidart\Modules\Tests\BaseTestCase;

class LaravelEloquentRepositoryTest extends BaseTestCase
{
    /**
     * @var LaravelEloquentRepository
     */
    private $repository;

    public function setUp()
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
        $this->createModule('DisabledModule');

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

    private function createModule($moduleName): ModuleEntity
    {
        $moduleEntity = new ModuleEntity();
        $moduleEntity->name = $moduleName;
        $moduleEntity->path = __DIR__ . "/../stubs/valid/{$moduleName}";
        $moduleEntity->save();

        return $moduleEntity;
    }
}
