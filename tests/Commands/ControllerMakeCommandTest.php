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

it('generates a new controller class', function () {
    $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Http/Controllers/MyController.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('appends controller to name if not present', function () {
    $code = $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Http/Controllers/MyController.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('appends controller to class name if not present', function () {
    $code = $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates a plain controller', function () {
    $code = $this->artisan('module:make-controller', [
        'controller' => 'MyController',
        'module' => 'Blog',
        '--plain' => true,
    ]);

    $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates an api controller', function () {
    $code = $this->artisan('module:make-controller', [
        'controller' => 'MyController',
        'module' => 'Blog',
        '--api' => true,
    ]);

    $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.controller.path', 'Controllers');

    $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Controllers/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.controller.namespace', 'Controllers');

    $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can generate a controller in sub namespace in correct folder', function () {
    $code = $this->artisan('module:make-controller', ['controller' => 'Api\\MyController', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Http/Controllers/Api/MyController.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('can generate a controller in sub namespace with correct generated file', function () {
    $code = $this->artisan('module:make-controller', ['controller' => 'Api\\MyController', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Http/Controllers/Api/MyController.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
