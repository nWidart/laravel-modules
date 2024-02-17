<?php

uses(\Nwidart\Modules\Laravel\Module::class);
use \Nwidart\Modules\Tests\Activators\TestModule;
use Nwidart\Modules\Activators\FileActivator;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->module = new TestModule($this->app, 'Recipe', __DIR__ . '/stubs/valid/Recipe');
    $this->finder = $this->app['files'];
    $this->activator = new FileActivator($this->app);
});

afterEach(function () {
    $this->activator->reset();
});

it('creates valid json file after enabling', function () {
    $this->activator->enable($this->module);
    $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

    $this->activator->setActive($this->module, true);
    $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
});

it('creates valid json file after disabling', function () {
    $this->activator->disable($this->module);
    $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

    $this->activator->setActive($this->module, false);
    $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
});

it('can check module enabled status', function () {
    $this->activator->enable($this->module);
    expect($this->activator->hasStatus($this->module, true))->toBeTrue();

    $this->activator->setActive($this->module, true);
    expect($this->activator->hasStatus($this->module, true))->toBeTrue();
});

it('can check module disabled status', function () {
    $this->activator->disable($this->module);
    expect($this->activator->hasStatus($this->module, false))->toBeTrue();

    $this->activator->setActive($this->module, false);
    expect($this->activator->hasStatus($this->module, false))->toBeTrue();
});

it('can check status of module that hasnt been enabled or disabled', function () {
    expect($this->activator->hasStatus($this->module, false))->toBeTrue();
});

function registerProviders() : void
{
    registerProviders();
}
