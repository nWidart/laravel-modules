<?php

namespace Nwidart\Modules;

use Nwidart\Modules\Laravel\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class CollectionTest extends BaseTestCase
{
    /** @test */
    public function toArraySetsPathAttribute()
    {
        $moduleOnePath = __DIR__ . '/stubs/valid/Recipe';
        $moduleTwoPath = __DIR__ . '/stubs/valid/Requirement';
        $modules = [
            new Module($this->app, 'module-one', $moduleOnePath),
            new Module($this->app, 'module-two', $moduleTwoPath),
        ];
        $collection = new Collection($modules);
        $collectionArray = $collection->toArray();

        $this->assertArrayHasKey('path', $collectionArray[0]);
        $this->assertEquals($moduleOnePath, $collectionArray[0]['path']);
        $this->assertArrayHasKey('path', $collectionArray[1]);
        $this->assertEquals($moduleTwoPath, $collectionArray[1]['path']);
    }
}
