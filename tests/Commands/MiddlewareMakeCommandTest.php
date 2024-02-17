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

it('generates a new middleware class', function () {
    $code = $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Http/Middleware/SomeMiddleware.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Http/Middleware/SomeMiddleware.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.filter.path', 'Middleware');

    $code = $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Middleware/SomeMiddleware.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.filter.namespace', 'Middleware');

    $code = $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Http/Middleware/SomeMiddleware.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
