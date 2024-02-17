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

it('generates a new resource class', function () {
    $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Transformers/PostsTransformer.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Transformers/PostsTransformer.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can generate a collection resource class', function () {
    $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog', '--collection' => true]);

    $file = $this->finder->get($this->modulePath . '/Transformers/PostsTransformer.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.resource.path', 'Http/Resources');

    $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog', '--collection' => true]);

    $file = $this->finder->get($this->modulePath . '/Http/Resources/PostsTransformer.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.resource.namespace', 'Http\\Resources');

    $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog', '--collection' => true]);

    $file = $this->finder->get($this->modulePath . '/Transformers/PostsTransformer.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
