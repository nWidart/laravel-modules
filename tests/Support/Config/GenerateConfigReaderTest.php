<?php

namespace Nwidart\Modules\Tests\Support\Config;

use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Config\GeneratorPath;
use Nwidart\Modules\Tests\BaseTestCase;

final class GenerateConfigReaderTest extends BaseTestCase
{
    public function test_it_can_read_a_configuration_value_with_new_format()
    {
        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('database/seeders', $seedConfig->path());
        $this->assertTrue($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_new_format_set_to_false()
    {
        $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'database/seeders', 'generate' => false]);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('database/seeders', $seedConfig->path());
        $this->assertFalse($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_old_format()
    {
        $this->app['config']->set('modules.paths.generator.seeder', 'database/seeders');

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('database/seeders', $seedConfig->path());
        $this->assertTrue($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_old_format_set_to_false()
    {
        $this->app['config']->set('modules.paths.generator.seeder', false);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEmpty($seedConfig->path());
        $this->assertFalse($seedConfig->generate());
    }

    public function test_it_can_guess_namespace_from_path()
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

        $config = GenerateConfigReader::read('provider');

        $this->assertEquals('Base/Providers', $config->path());
        $this->assertEquals('Base\Providers', $config->namespace());
    }
}
