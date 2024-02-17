<?php

uses(\Nwidart\Modules\Laravel\Module::class);
use Illuminate\Support\Facades\Event;
use Modules\Recipe\Providers\DeferredServiceProvider;
use Modules\Recipe\Providers\RecipeServiceProvider;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Json;
use Nwidart\Modules\Tests\TestingModule;

beforeEach(function () {
    $this->module = new TestingModule($this->app, 'Recipe Name', __DIR__ . '/stubs/valid/Recipe');
    $this->activator = $this->app[ActivatorInterface::class];
});

afterEach(function () {
    $this->activator->reset();
});

beforeAll(function () {
    symlink(__DIR__ . '/stubs/valid', __DIR__ . '/stubs/valid_symlink');
});

afterAll(function () {
    unlink(__DIR__ . '/stubs/valid_symlink');
});

it('gets module name', function () {
    expect($this->module->getName())->toEqual('Recipe Name');
});

it('gets lowercase module name', function () {
    expect($this->module->getLowerName())->toEqual('recipe name');
});

it('gets studly name', function () {
    expect($this->module->getStudlyName())->toEqual('RecipeName');
});

it('gets snake name', function () {
    expect($this->module->getSnakeName())->toEqual('recipe_name');
});

it('gets module description', function () {
    expect($this->module->getDescription())->toEqual('recipe module');
});

it('gets module path', function () {
    expect($this->module->getPath())->toEqual(__DIR__ . '/stubs/valid/Recipe');
});

it('gets module path with symlink', function () {
    // symlink created in setUpBeforeClass
    $this->module = new TestingModule($this->app, 'Recipe Name', __DIR__ . '/stubs/valid_symlink/Recipe');

    expect($this->module->getPath())->toEqual(__DIR__ . '/stubs/valid_symlink/Recipe');

    // symlink deleted in tearDownAfterClass
});

it('loads module translations', function () {
    (new TestingModule($this->app, 'Recipe', __DIR__ . '/stubs/valid/Recipe'))->boot();
    expect(trans('recipe::recipes.title.recipes'))->toEqual('Recipe');
});

it('reads module json files', function () {
    $jsonModule = $this->module->json();
    $composerJson = $this->module->json('composer.json');

    expect($jsonModule)->toBeInstanceOf(Json::class);
    expect($jsonModule->get('version'))->toEqual('0.1');
    expect($composerJson)->toBeInstanceOf(Json::class);
    expect($composerJson->get('type'))->toEqual('asgard-module');
});

it('reads key from module json file via helper method', function () {
    expect($this->module->get('name'))->toEqual('Recipe');
    expect($this->module->get('version'))->toEqual('0.1');
    expect($this->module->get('some-thing-non-there', 'my default'))->toEqual('my default');
    expect($this->module->get('requires'))->toEqual(['required_module']);
});

it('reads key from composer json file via helper method', function () {
    expect($this->module->getComposerAttr('name'))->toEqual('nwidart/recipe');
});

it('casts module to string', function () {
    expect((string) $this->module)->toEqual('RecipeName');
});

it('module status check', function () {
    expect($this->module->isStatus(true))->toBeFalse();
    expect($this->module->isStatus(false))->toBeTrue();
});

it('checks module enabled status', function () {
    expect($this->module->isEnabled())->toBeFalse();
    expect($this->module->isDisabled())->toBeTrue();
});

it('sets active status', function () {
    $this->module->setActive(true);
    expect($this->module->isEnabled())->toBeTrue();
    $this->module->setActive(false);
    expect($this->module->isEnabled())->toBeFalse();
});

it('fires events when module is enabled', function () {
    Event::fake();

    $this->module->enable();

    Event::assertDispatched(sprintf('modules.%s.enabling', $this->module->getLowerName()));
    Event::assertDispatched(sprintf('modules.%s.enabled', $this->module->getLowerName()));
});

it('fires events when module is disabled', function () {
    Event::fake();

    $this->module->disable();

    Event::assertDispatched(sprintf('modules.%s.disabling', $this->module->getLowerName()));
    Event::assertDispatched(sprintf('modules.%s.disabled', $this->module->getLowerName()));
});

it('has a good providers manifest path', function () {
    expect($this->module->getCachedServicesPath())->toEqual($this->app->bootstrapPath("cache/{$this->module->getSnakeName()}_module.php"));
});

it('makes a manifest file when providers are loaded', function () {
    $cachedServicesPath = $this->module->getCachedServicesPath();

    @unlink($cachedServicesPath);
    $this->assertFileDoesNotExist($cachedServicesPath);

    registerProviders();

    expect($cachedServicesPath)->toBeFile();
    $manifest = require $cachedServicesPath;

    expect($manifest)->toEqual([
        'providers' => [
            RecipeServiceProvider::class,
            DeferredServiceProvider::class,
        ],
        'eager' => [RecipeServiceProvider::class],
        'deferred' => ['deferred' => DeferredServiceProvider::class],
        'when' =>
            [DeferredServiceProvider::class => []],
    ]);
});

it('can load a deferred provider', function () {
    @unlink($this->module->getCachedServicesPath());

    registerProviders();

    try {
        app('foo');
        expect(false)->toBeTrue("app('foo') should throw an exception.");
    } catch (\Exception $e) {
        expect($e->getMessage())->toEqual('Target class [foo] does not exist.');
    }

    app('deferred');

    expect(app('foo'))->toEqual('bar');
});

it('can load assets is empty when no manifest exists', function () {
    expect($this->module->getAssets())->toEqual([]);
});
