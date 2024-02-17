<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
});

it('makes factory', function () {
    $code = $this->artisan('module:make-factory', ['name' => 'Post', 'module' => 'Blog']);

    $factoryFile = $this->modulePath . '/Database/factories/PostFactory.php';

    expect(is_file($factoryFile))->toBeTrue('Factory file was not created.');
    $this->assertMatchesSnapshot($this->finder->get($factoryFile));
    expect($code)->toBe(0);
});
