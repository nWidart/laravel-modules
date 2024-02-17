<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
    $this->finder->put($this->modulePath . '/Assets/script.js', 'assetfile');
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
});

it('published module assets', function () {
    $code = $this->artisan('module:publish', ['module' => 'Blog']);

    expect(is_file(public_path('modules/blog/script.js')))->toBeTrue();
    expect($code)->toBe(0);
});
