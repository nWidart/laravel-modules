<?php

namespace Nwidart\Modules\Tests\Actuator;

use Nwidart\Modules\Tests\Actuator\Concerns\RegistersModuleProviders;
use Nwidart\Modules\Tests\BaseTestCase;

/**
 * The actuator is installed in dev, but its service provider is not registered here.
 */
class WithoutActuatorTest extends BaseTestCase
{
    use RegistersModuleProviders;

    public function test_it_registers_no_modules_indicator_without_the_actuator_provider()
    {
        $this->assertNull(config('actuator'));
    }

    public function test_a_module_provider_registers_no_indicators_without_the_actuator_provider()
    {
        $this->registerModuleProvider();

        $this->assertNull(config('actuator'));
    }
}
