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

it('makes observer', function () {
    $code = $this->artisan('module:make-observer', ['name' => 'Post', 'module' => 'Blog']);

    $observerFile = $this->modulePath . '/Observers/PostObserver.php';

    // dd($observerFile);
    expect(is_file($observerFile))->toBeTrue('Observer file was not created.');
    $this->assertMatchesSnapshot($this->finder->get($observerFile));
    expect($code)->toBe(0);
});
