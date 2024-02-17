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

it('published module translations', function () {
    $code = $this->artisan('module:publish-translation', ['module' => 'Blog']);

    expect(base_path('resources/lang/blog'))->toBeDirectory();
    expect($code)->toBe(0);
});
