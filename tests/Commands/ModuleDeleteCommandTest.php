<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Activators\FileActivator;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->finder = $this->app['files'];
    $this->activator = new FileActivator($this->app);
});

it('can delete a module from disk', function () {
    $this->artisan('module:make', ['name' => ['WrongModule']]);
    expect(base_path('modules/WrongModule'))->toBeDirectory();

    $code = $this->artisan('module:delete', ['module' => 'WrongModule']);
    $this->assertFileDoesNotExist(base_path('modules/WrongModule'));
    expect($code)->toBe(0);
});

it('deletes modules from status file', function () {
    $this->artisan('module:make', ['name' => ['WrongModule']]);
    $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

    $code = $this->artisan('module:delete', ['module' => 'WrongModule']);
    $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    expect($code)->toBe(0);
});
