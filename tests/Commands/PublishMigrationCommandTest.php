<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
    $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
    $this->finder->delete($this->finder->allFiles(base_path('database/migrations')));
});

it('publishes module migrations', function () {
    $code = $this->artisan('module:publish-migration', ['module' => 'Blog']);

    $files = $this->finder->allFiles(base_path('database/migrations'));

    expect($files)->toHaveCount(1);
    expect($code)->toBe(0);
});
