<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Facades\Module;

class ModuleFacadeTest extends BaseTestCase
{
    public function test_it_resolves_the_module_facade()
    {
        $modules = Module::all();

        $this->assertTrue(is_array($modules));
    }

    public function test_it_creates_macros_via_facade()
    {
        $modules = Module::macro('testMacro', function () {
            return true;
        });

        $this->assertTrue(Module::hasMacro('testMacro'));
    }

    public function test_it_calls_macros_via_facade()
    {
        $modules = Module::macro('testMacro', function () {
            return 'a value';
        });

        $this->assertEquals('a value', Module::testMacro());
    }
}
