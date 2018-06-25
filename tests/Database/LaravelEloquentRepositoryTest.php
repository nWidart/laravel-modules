<?php
declare(strict_types=1);

namespace Nwidart\Modules\Tests\Database;

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
        $this->createModule('Test DB Module');

        $this->assertCount(1, $this->repository->all());
    }

    private function createModule($moduleName): ModuleEntity
    {
        $moduleEntity = new ModuleEntity();
        $moduleEntity->name = $moduleName;
        $moduleEntity->module_path = __DIR__ . "/../stubs/valid/{$moduleName}";
        $moduleEntity->save();

        return $moduleEntity;
    }
}
