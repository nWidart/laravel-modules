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

it('generates the component class', function () {
    $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);
    expect(is_file($this->modulePath . '/View/Component/Blog.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generates the component view from component class command', function () {
    $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);
    $file = $this->finder->get($this->modulePath . '/Resources/views/components/blog.blade.php');
    expect(str_contains($file, '<div>'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);
    $file = $this->finder->get($this->modulePath . '/View/Component/Blog.php');
    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.component-class.path', 'View/Component/newDirectory');

    $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/View/Component/newDirectory/Blog.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
