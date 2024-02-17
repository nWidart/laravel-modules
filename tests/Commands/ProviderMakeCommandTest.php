<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true, ]);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
});

it('generates a service provider', function () {
    $code = $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Providers/MyBlogServiceProvider.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Providers/MyBlogServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates a master service provider with resource loading', function () {
    $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

    $file = $this->finder->get($this->modulePath . '/Providers/BlogServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can have custom migration resources location paths', function () {
    $this->app['config']->set('modules.paths.generator.migration', 'migrations');
    $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

    $file = $this->finder->get($this->modulePath . '/Providers/BlogServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

    $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

    $file = $this->finder->get($this->modulePath . '/SuperProviders/BlogServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.provider.namespace', 'SuperProviders');

    $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

    $file = $this->finder->get($this->modulePath . '/Providers/BlogServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
