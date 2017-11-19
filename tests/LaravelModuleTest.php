<?php

namespace Nwidart\Modules\Tests;

use Modules\Recipe\Providers\DeferredServiceProvider;
use Modules\Recipe\Providers\RecipeServiceProvider;
use Nwidart\Modules\Json;
use Nwidart\Modules\Module;

class ModuleTest extends BaseTestCase
{
    /**
     * @var TestingModule
     */
    private $module;

    public function setUp()
    {
        parent::setUp();
        $this->module = new TestingModule($this->app, 'Recipe Name', __DIR__ . '/stubs/valid/Recipe');
    }

    /** @test */
    public function it_gets_module_name()
    {
        $this->assertEquals('Recipe Name', $this->module->getName());
    }

    /** @test */
    public function it_gets_lowercase_module_name()
    {
        $this->assertEquals('recipe name', $this->module->getLowerName());
    }

    /** @test */
    public function it_gets_studly_name()
    {
        $this->assertEquals('RecipeName', $this->module->getStudlyName());
    }

    /** @test */
    public function it_gets_snake_name()
    {
        $this->assertEquals('recipe_name', $this->module->getSnakeName());
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
        $this->assertEquals(__DIR__ . '/stubs/valid/Recipe', $this->module->getPath());
    }

    /** @test */
    public function it_gets_required_modules()
    {
        $this->assertEquals(['required_module'], $this->module->getRequires());
    }

    /** @test */
    public function it_loads_module_translations()
    {
        (new TestingModule($this->app, 'Recipe', __DIR__ . '/stubs/valid/Recipe'))->boot();
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
        $this->assertEquals(['required_module'], $this->module->get('requires'));
    }

    /** @test */
    public function it_reads_key_from_composer_json_file_via_helper_method()
    {
        $this->assertEquals('nwidart/recipe', $this->module->getComposerAttr('name'));
    }

    /** @test */
    public function it_casts_module_to_string()
    {
        $this->assertEquals('RecipeName', (string) $this->module);
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

    /** @test */
    public function it_has_a_good_providers_manifest_path()
    {
        $this->assertEquals(
            $this->app->bootstrapPath("cache/{$this->module->getSnakeName()}_module.php"),
            $this->module->getCachedServicesPath()
        );
    }

    /** @test */
    public function it_makes_a_manifest_file_when_providers_are_loaded()
    {
        $cachedServicesPath = $this->module->getCachedServicesPath();

        @unlink($cachedServicesPath);
        $this->assertFileNotExists($cachedServicesPath);

        $this->module->registerProviders();

        $this->assertFileExists($cachedServicesPath);
        $manifest = require $cachedServicesPath;

        $this->assertEquals([
            'providers' => [
                RecipeServiceProvider::class,
                DeferredServiceProvider::class,
            ],
            'eager'     => [RecipeServiceProvider::class],
            'deferred'  => ['deferred' => DeferredServiceProvider::class],
            'when'      =>
                [DeferredServiceProvider::class => []],
        ], $manifest);
    }

    /** @test */
    public function it_can_load_a_deferred_provider()
    {
        @unlink($this->module->getCachedServicesPath());

        $this->module->registerProviders();

        try {
            app('foo');
            $this->assertTrue(false, "app('foo') should throw an exception.");
        } catch (\Exception $e) {
            $this->assertEquals("Class foo does not exist", $e->getMessage());
        }

        app('deferred');

        $this->assertEquals('bar', app('foo'));
    }
}

class TestingModule extends \Nwidart\Modules\Laravel\Module
{
    public function registerProviders()
    {
        parent::registerProviders();
    }
}
