<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Support\Facades\Event;
use Modules\Recipe\Providers\DeferredServiceProvider;
use Modules\Recipe\Providers\RecipeServiceProvider;
use Nwidart\Modules\Constants\ModuleEvent;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Json;

class LaravelModuleTest extends BaseTestCase
{
    /**
     * @var TestingModule
     */
    private $module;

    /**
     * @var ActivatorInterface
     */
    private $activator;

    public function setUp(): void
    {
        parent::setUp();
        $this->module = new TestingModule($this->app, 'Recipe Name', __DIR__.'/stubs/valid/Recipe');
        $this->activator = $this->app[ActivatorInterface::class];
    }

    public function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        symlink(__DIR__.'/stubs/valid', __DIR__.'/stubs/valid_symlink');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        unlink(__DIR__.'/stubs/valid_symlink');
    }

    public function test_it_gets_module_name()
    {
        $this->assertEquals('Recipe Name', $this->module->getName());
    }

    public function test_it_gets_lowercase_module_name()
    {
        $this->assertEquals('recipe name', $this->module->getLowerName());
    }

    public function test_it_gets_studly_name()
    {
        $this->assertEquals('RecipeName', $this->module->getStudlyName());
    }

    public function test_it_gets_snake_name()
    {
        $this->assertEquals('recipe_name', $this->module->getSnakeName());
    }

    public function test_it_gets_module_description()
    {
        $this->assertEquals('recipe module', $this->module->getDescription());
    }

    public function test_it_gets_module_path()
    {
        $this->assertEquals(__DIR__.'/stubs/valid/Recipe', $this->module->getPath());
    }

    public function test_it_gets_module_path_with_symlink()
    {
        // symlink created in setUpBeforeClass

        $this->module = new TestingModule($this->app, 'Recipe Name', __DIR__.'/stubs/valid_symlink/Recipe');

        $this->assertEquals(__DIR__.'/stubs/valid_symlink/Recipe', $this->module->getPath());

        // symlink deleted in tearDownAfterClass
    }

    public function test_it_loads_module_translations()
    {
        (new TestingModule($this->app, 'Recipe', __DIR__.'/stubs/valid/Recipe'))->boot();
        $this->assertEquals('Recipe', trans('recipe::recipes.title.recipes'));
    }

    public function test_it_reads_module_json_files()
    {
        $jsonModule = $this->module->json();
        $composerJson = $this->module->json('composer.json');

        $this->assertInstanceOf(Json::class, $jsonModule);
        $this->assertEquals('0.1', $jsonModule->get('version'));
        $this->assertInstanceOf(Json::class, $composerJson);
        $this->assertEquals('asgard-module', $composerJson->get('type'));
    }

    public function test_it_reads_key_from_module_json_file_via_helper_method()
    {
        $this->assertEquals('Recipe', $this->module->get('name'));
        $this->assertEquals('0.1', $this->module->get('version'));
        $this->assertEquals('my default', $this->module->get('some-thing-non-there', 'my default'));
        $this->assertEquals(['required_module'], $this->module->get('requires'));
    }

    public function test_it_reads_key_from_composer_json_file_via_helper_method()
    {
        $this->assertEquals('nwidart/recipe', $this->module->getComposerAttr('name'));
    }

    public function test_it_casts_module_to_string()
    {
        $this->assertEquals('RecipeName', (string) $this->module);
    }

    public function test_it_module_status_check()
    {
        $this->assertFalse($this->module->isStatus(true));
        $this->assertTrue($this->module->isStatus(false));
    }

    public function test_it_checks_module_enabled_status()
    {
        $this->assertFalse($this->module->isEnabled());
        $this->assertTrue($this->module->isDisabled());
    }

    public function test_it_sets_active_status(): void
    {
        $this->module->setActive(true);
        $this->assertTrue($this->module->isEnabled());
        $this->module->setActive(false);
        $this->assertFalse($this->module->isEnabled());
    }

    public function test_it_fires_events_when_module_is_enabled()
    {
        Event::fake();

        $this->module->enable();

        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::ENABLING, $this->module->getLowerName()));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::ENABLED, $this->module->getLowerName()));
    }

    public function test_it_fires_events_when_module_is_disabled()
    {
        Event::fake();

        $this->module->disable();

        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::DISABLING, $this->module->getLowerName()));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::DISABLED, $this->module->getLowerName()));
    }

    public function test_it_has_a_good_providers_manifest_path()
    {
        $this->assertEquals(
            $this->app->bootstrapPath("cache/{$this->module->getSnakeName()}_module.php"),
            $this->module->getCachedServicesPath()
        );
    }

    public function test_it_makes_a_manifest_file_when_providers_are_loaded()
    {
        $cachedServicesPath = $this->module->getCachedServicesPath();

        @unlink($cachedServicesPath);
        $this->assertFileDoesNotExist($cachedServicesPath);

        $this->module->registerProviders();

        $this->assertFileExists($cachedServicesPath);
        $manifest = require $cachedServicesPath;

        $this->assertEquals([
            'providers' => [
                RecipeServiceProvider::class,
                DeferredServiceProvider::class,
            ],
            'eager' => [RecipeServiceProvider::class],
            'deferred' => ['deferred' => DeferredServiceProvider::class],
            'when' => [DeferredServiceProvider::class => []],
        ], $manifest);
    }

    public function test_it_can_load_a_deferred_provider()
    {
        @unlink($this->module->getCachedServicesPath());

        $this->module->registerProviders();

        try {
            app('foo');
            $this->assertTrue(false, "app('foo') should throw an exception.");
        } catch (\Exception $e) {
            $this->assertEquals('Target class [foo] does not exist.', $e->getMessage());
        }

        app('deferred');

        $this->assertEquals('bar', app('foo'));
    }

    public function test_it_can_load_assets_is_empty_when_no_manifest_exists()
    {
        $this->assertEquals([], $this->module->getAssets());
    }
}

class TestingModule extends \Nwidart\Modules\Laravel\Module
{
    public function registerProviders(): void
    {
        parent::registerProviders();
    }
}
