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
        $this->assertEquals('database/seeders', $seedConfig->getPath());
        $this->assertTrue($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_new_format_set_to_false()
    {
        $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertFalse($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_old_format()
    {
        $this->app['config']->set('modules.paths.generator.seeder', 'Database/Seeders');

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertTrue($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_old_format_set_to_false()
    {
        $this->app['config']->set('modules.paths.generator.seeder', false);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertFalse($seedConfig->getPath());
        $this->assertFalse($seedConfig->generate());
    }

    public function test_it_can_guess_namespace_from_path()
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

        $config = GenerateConfigReader::read('provider');

        $this->assertEquals('Base/Providers', $config->getPath());
        $this->assertEquals('Base\Providers', $config->getNamespace());
    }

    public function test_it_strips_the_app_folder_as_a_prefix_not_a_character_set()
    {
        $this->app['config']->set('modules.paths.generator.actions', ['path' => 'app/actions', 'generate' => true]);

        $this->assertEquals('Actions', GenerateConfigReader::read('actions')->getNamespace());
    }

    public function test_it_keeps_paths_outside_a_custom_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'domain/');
        $this->app['config']->set('modules.paths.generator.factory', ['path' => 'database/factories', 'generate' => true]);
        $this->app['config']->set('modules.paths.generator.model', 'domain/Models');

        $this->assertEquals('Database\Factories', GenerateConfigReader::read('factory')->getNamespace());
        $this->assertEquals('Models', GenerateConfigReader::read('model')->getNamespace());
    }

    public function test_it_does_not_generate_an_entry_without_a_generate_key()
    {
        $this->app['config']->set('modules.paths.generator.actions', ['path' => 'app/Domain/Actions']);

        $config = GenerateConfigReader::read('actions');

        $this->assertEquals('app/Domain/Actions', $config->getPath());
        $this->assertFalse($config->generate());
        $this->assertEquals('Domain\Actions', $config->getNamespace());
    }

    public function test_it_reads_an_entry_with_only_a_namespace()
    {
        $this->app['config']->set('modules.paths.generator.actions', ['namespace' => 'Domain\Actions']);

        $config = GenerateConfigReader::read('actions');

        $this->assertNull($config->getPath());
        $this->assertFalse($config->generate());
        $this->assertTrue($config->generatesWith(true));
        $this->assertEquals('Domain\Actions', $config->getNamespace());
    }
}
