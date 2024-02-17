<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Illuminate\Support\Str;


beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
});

afterEach(function () {
    $this->finder->deleteDirectory($this->modulePath);
});

it('finds the module path', function () {
    expect(Str::contains(module_path('Blog'), 'modules/Blog'))->toBeTrue();
});

it('can bind a relative path to module path', function () {
    expect(Str::contains(module_path('Blog', 'config/config.php'), 'modules/Blog/config/config.php'))->toBeTrue();
});
