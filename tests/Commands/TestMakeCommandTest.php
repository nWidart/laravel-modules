<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
    $this->activator = $this->app[ActivatorInterface::class];
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
    $this->activator->reset();
});

it('generates a new test class', function () {
    $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);
    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

    expect(is_file($this->modulePath . '/Tests/Unit/EloquentPostRepositoryTest.php'))->toBeTrue();
    expect(is_file($this->modulePath . '/Tests/Feature/EloquentPostRepositoryTest.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct unit file with content', function () {
    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Tests/Unit/EloquentPostRepositoryTest.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generated correct feature file with content', function () {
    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

    $file = $this->finder->get($this->modulePath . '/Tests/Feature/EloquentPostRepositoryTest.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default unit namespace', function () {
    $this->app['config']->set('modules.paths.generator.test.path', 'SuperTests/Unit');

    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/SuperTests/Unit/EloquentPostRepositoryTest.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default unit namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.test.namespace', 'SuperTests\\Unit');

    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Tests/Unit/EloquentPostRepositoryTest.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default feature namespace', function () {
    $this->app['config']->set('modules.paths.generator.test-feature.path', 'SuperTests/Feature');

    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

    $file = $this->finder->get($this->modulePath . '/SuperTests/Feature/EloquentPostRepositoryTest.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default feature namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.test-feature.namespace', 'SuperTests\\Feature');

    $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

    $file = $this->finder->get($this->modulePath . '/Tests/Feature/EloquentPostRepositoryTest.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
