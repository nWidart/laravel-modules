<?php

namespace Nwidart\Modules\Tests\Entities;

use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Tests\BaseTestCase;

class ModuleEntityTest extends BaseTestCase
{
    /** @test */
    public function it_sets_up_module_entity()
    {
        $moduleEntity = new ModuleEntity();
        $moduleEntity->name = 'Test DB Module';
        $moduleEntity->save();

        $this->assertCount(1, ModuleEntity::all());
    }
}
