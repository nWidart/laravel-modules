<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
});

it('can list modules', function () {
    $code = $this->artisan('module:list');

    // We just want to make sure nothing throws an exception inside the list command
    expect(true)->toBeTrue();
    expect($code)->toBe(0);
});
