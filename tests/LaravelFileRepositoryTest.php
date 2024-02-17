<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Exceptions\InvalidAssetPath;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Module;

beforeEach(function () {
    $this->repository = new LaravelFileRepository($this->app);
    $this->activator = $this->app[ActivatorInterface::class];
});

afterEach(function () {
    $this->activator->reset();
});

it('adds location to paths', function () {
    $this->repository->addLocation('some/path');

    $paths = $this->repository->getPaths();
    expect($paths)->toHaveCount(1);
    expect($paths[0])->toEqual('some/path');
});

it('returns a collection', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    expect($this->repository->toCollection())->toBeInstanceOf(Collection::class);
    expect($this->repository->collections())->toBeInstanceOf(Collection::class);
});

it('returns all enabled modules', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    expect($this->repository->getByStatus(true))->toHaveCount(0);
    expect($this->repository->allEnabled())->toHaveCount(0);
});

it('returns all disabled modules', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    expect($this->repository->getByStatus(false))->toHaveCount(2);
    expect($this->repository->allDisabled())->toHaveCount(2);
});

it('counts all modules', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    expect($this->repository->count())->toEqual(2);
});

it('finds a module', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    expect($this->repository->find('recipe'))->toBeInstanceOf(Module::class);
});

it('find or fail throws exception if module not found', function () {
    $this->expectException(ModuleNotFoundException::class);

    $this->repository->findOrFail('something');
});

it('finds the module asset path', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid/Recipe');
    $assetPath = $this->repository->assetPath('recipe');

    expect($assetPath)->toEqual(public_path('modules/recipe'));
});

it('gets the used storage path', function () {
    $path = $this->repository->getUsedStoragePath();

    expect($path)->toEqual(storage_path('app/modules/modules.used'));
});

it('sets used module', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    $this->repository->setUsed('Recipe');

    expect($this->repository->getUsedNow())->toEqual('Recipe');
});

it('returns laravel filesystem', function () {
    expect($this->repository->getFiles())->toBeInstanceOf(Filesystem::class);
});

it('gets the assets path', function () {
    expect($this->repository->getAssetsPath())->toEqual(public_path('modules'));
});

it('gets a specific module asset', function () {
    $path = $this->repository->asset('recipe:test.js');

    expect($path)->toEqual('//localhost/modules/recipe/test.js');
});

it('throws exception if module is omitted', function () {
    $this->expectException(InvalidAssetPath::class);
    $this->expectExceptionMessage('Module name was not specified in asset [test.js].');

    $this->repository->asset('test.js');
});

it('can detect if module is active', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    $this->repository->enable('Recipe');

    expect($this->repository->isEnabled('Recipe'))->toBeTrue();
});

it('can detect if module is inactive', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    $this->repository->isDisabled('Recipe');

    expect($this->repository->isDisabled('Recipe'))->toBeTrue();
});

it('can get and set the stubs path', function () {
    $this->repository->setStubPath('some/stub/path');

    expect($this->repository->getStubPath())->toEqual('some/stub/path');
});

it('gets the configured stubs path if enabled', function () {
    $this->app['config']->set('modules.stubs.enabled', true);

    expect($this->repository->getStubPath())->toEqual(base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'));
});

it('returns default stub path', function () {
    expect($this->repository->getStubPath())->toBeNull();
});

it('can disabled a module', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    $this->repository->disable('Recipe');

    expect($this->repository->isDisabled('Recipe'))->toBeTrue();
});

it('can enable a module', function () {
    $this->repository->addLocation(__DIR__ . '/stubs/valid');

    $this->repository->enable('Recipe');

    expect($this->repository->isEnabled('Recipe'))->toBeTrue();
});

it('can delete a module', function () {
    $this->artisan('module:make', ['name' => ['Blog']]);

    $this->repository->delete('Blog');

    expect(is_dir(base_path('modules/Blog')))->toBeFalse();
});

it('can register macros', function () {
    Module::macro('registeredMacro', function () {
    });

    expect(Module::hasMacro('registeredMacro'))->toBeTrue();
});

it('does not have unregistered macros', function () {
    expect(Module::hasMacro('unregisteredMacro'))->toBeFalse();
});

it('calls macros on modules', function () {
    Module::macro('getReverseName', function () {
        return strrev($this->getLowerName());
    });

    $this->repository->addLocation(__DIR__ . '/stubs/valid');
    $module = $this->repository->find('recipe');

    expect($module->getReverseName())->toEqual('epicer');
});
