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

it('generates a new service provider class', function () {
    $path = $this->modulePath . '/Providers/RouteServiceProvider.php';
    $this->finder->delete($path);
    $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

    expect(is_file($path))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $path = $this->modulePath . '/Providers/RouteServiceProvider.php';
    $this->finder->delete($path);
    $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

    $file = $this->finder->get($path);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

    $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperProviders/RouteServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.provider.namespace', 'SuperProviders');

    $path = $this->modulePath . '/Providers/RouteServiceProvider.php';
    $this->finder->delete($path);
    $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

    $file = $this->finder->get($path);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can overwrite route file names', function () {
    $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');
    $this->app['config']->set('modules.stubs.files.routes/api', 'SuperRoutes/api.php');

    $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);

    $file = $this->finder->get($this->modulePath . '/Providers/RouteServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can overwrite file', function () {
    $this->artisan('module:route-provider', ['module' => 'Blog']);
    $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');

    $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);
    $file = $this->finder->get($this->modulePath . '/Providers/RouteServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the custom controller namespace', function () {
    $this->app['config']->set('modules.paths.generator.controller.path', 'Base/Http/Controllers');
    $this->app['config']->set('modules.paths.generator.provider.path', 'Base/Providers');

    $code = $this->artisan('module:route-provider', ['module' => 'Blog']);
    $file = $this->finder->get($this->modulePath . '/Base/Providers/RouteServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
