<?php

namespace Nwidart\Modules\tests;

use Nwidart\Modules\Json;
use Nwidart\Modules\Module;

class ModuleTest extends BaseTestCase
{
    /**
     * @var Module
     */
    private $module;

    public function setUp()
    {
        parent::setUp();
        $this->module = new Module($this->app, 'Recipe', __DIR__ . '/stubs/Recipe');
    }

    /** @test */
    public function it_gets_module_name()
    {
        $this->assertEquals('Recipe', $this->module->getName());
    }

    /** @test */
    public function it_gets_lowercase_module_name()
    {
        $this->assertEquals('recipe', $this->module->getLowerName());
    }

    /** @test */
    public function it_gets_studly_name()
    {
        $this->assertEquals('Recipe', $this->module->getName());
    }

    /** @test */
    public function it_gets_module_description()
    {
        $this->assertEquals('recipe module', $this->module->getDescription());
    }

    /** @test */
    public function it_gets_module_alias()
    {
        $this->assertEquals('recipe', $this->module->getAlias());
    }

    /** @test */
    public function it_gets_module_path()
    {
        $this->assertEquals(__DIR__ . '/stubs/Recipe', $this->module->getPath());
    }

    /** @test */
    public function it_gets_required_modules()
    {
        $this->assertEquals([ 'required_module' ], $this->module->getRequires());
    }

    /** @test */
    public function it_loads_module_translations()
    {
        $this->module->boot();

        $this->assertEquals('Recipe', trans('recipe::recipes.title.recipes'));
    }

    /** @test */
    public function it_reads_module_json_files()
    {
        $jsonModule = $this->module->json();
        $composerJson = $this->module->json('composer.json');

        $this->assertInstanceOf(Json::class, $jsonModule);
        $this->assertEquals('0.1', $jsonModule->get('version'));
        $this->assertInstanceOf(Json::class, $composerJson);
        $this->assertEquals('asgard-module', $composerJson->get('type'));
    }

    /** @test */
    public function it_reads_key_from_module_json_file_via_helper_method()
    {
        $this->assertEquals('Recipe', $this->module->get('name'));
        $this->assertEquals('0.1', $this->module->get('version'));
        $this->assertEquals('my default', $this->module->get('some-thing-non-there', 'my default'));
        $this->assertEquals([ 'required_module' ], $this->module->get('requires'));
    }

    /** @test */
    public function it_reads_key_from_composer_json_file_via_helper_method()
    {
        $this->assertEquals('nwidart/recipe', $this->module->getComposerAttr('name'));
    }

    /** @test */
    public function it_casts_module_to_string()
    {
        $this->assertEquals('Recipe', (string) $this->module);
    }

    /** @test */
    public function it_module_status_check()
    {
        $this->assertTrue($this->module->isStatus(1));
        $this->assertFalse($this->module->isStatus(0));
    }

    /** @test */
    public function it_checks_module_enabled_status()
    {
        $this->assertTrue($this->module->enabled());
        $this->assertTrue($this->module->active());
        $this->assertFalse($this->module->notActive());
        $this->assertFalse($this->module->disabled());
    }

    /** @test */
    public function it_fires_events_when_module_is_disabled()
    {
        $this->expectsEvents([
            sprintf('modules.%s.disabling', $this->module->getLowerName()),
            sprintf('modules.%s.disabled', $this->module->getLowerName()),
        ]);

        $this->module->disable();
    }

    /** @test */
    public function it_fires_events_when_module_is_enabled()
    {
        $this->expectsEvents([
            sprintf('modules.%s.enabling', $this->module->getLowerName()),
            sprintf('modules.%s.enabled', $this->module->getLowerName()),
        ]);

        $this->module->enable();
    }
}
