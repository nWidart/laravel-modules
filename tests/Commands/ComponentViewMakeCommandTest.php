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

it('generates the component view', function () {
    $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);
    expect(is_file($this->modulePath . '/Resources/views/components/blog.blade.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);
    $file = $this->finder->get($this->modulePath . '/Resources/views/components/blog.blade.php');
    expect(str_contains($file, '<div>'))->toBeTrue();
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.component-view.path', 'Resources/views/components/newDirectory');

    $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Resources/views/components/newDirectory/blog.blade.php');

    expect(str_contains($file, '<div>'))->toBeTrue();
    expect($code)->toBe(0);
});
