<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Config\GeneratorPath;

final class GenerateConfigReaderTest extends BaseTestCase
{
    /** @test */
    public function it_can_read_a_configuration_value_with_new_format()
    {
        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertTrue($seedConfig->generate());
    }

    /** @test */
    public function it_can_read_a_configuration_value_with_new_format_set_to_false()
    {
        $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertFalse($seedConfig->generate());
    }

    /** @test */
    public function it_can_read_a_configuration_value_with_old_format()
    {
        $this->app['config']->set('modules.paths.generator.seeder', 'Database/Seeders');

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertTrue($seedConfig->generate());
    }

    /** @test */
    public function it_can_read_a_configuration_value_with_old_format_set_to_false()
    {
        $this->app['config']->set('modules.paths.generator.seeder', false);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertFalse($seedConfig->getPath());
        $this->assertFalse($seedConfig->generate());
    }
}
