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

it('generates a new migration class', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

    $files = $this->finder->allFiles($this->modulePath . '/Database/Migrations');

    expect($files)->toHaveCount(1);
    expect($code)->toBe(0);
});

it('generates correct create migration file content', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $fileName = $migrations[0]->getRelativePathname();
    $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates correct add migration file content', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'add_something_to_posts_table', 'module' => 'Blog']);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $fileName = $migrations[0]->getRelativePathname();
    $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates correct delete migration file content', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'delete_something_from_posts_table', 'module' => 'Blog']);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $fileName = $migrations[0]->getRelativePathname();
    $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates correct drop migration file content', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'drop_posts_table', 'module' => 'Blog']);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $fileName = $migrations[0]->getRelativePathname();
    $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates correct default migration file content', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'something_random_name', 'module' => 'Blog']);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $fileName = $migrations[0]->getRelativePathname();
    $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates foreign key constraints', function () {
    $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog', '--fields' => 'belongsTo:user:id:users']);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $fileName = $migrations[0]->getRelativePathname();
    $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
