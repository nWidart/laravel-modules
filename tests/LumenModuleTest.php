<?php

uses(\Nwidart\Modules\Lumen\Module::class);
use Illuminate\Support\Facades\Event;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Json;
use Nwidart\Modules\Tests\LumenTestingModule;

beforeEach(function () {
    $this->module = new LumenTestingModule($this->app, 'Recipe Name', __DIR__ . '/stubs/valid/Recipe');
    $this->activator = $this->app[ActivatorInterface::class];
});

afterEach(function () {
    $this->activator->reset();
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

it('loads module translations', function () {
    (new LumenTestingModule($this->app, 'Recipe', __DIR__ . '/stubs/valid/Recipe'))->boot();
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
    expect($this->module->getCachedServicesPath())->toEqual($this->app->basePath("storage/app/{$this->module->getSnakeName()}_module.php"));
});
