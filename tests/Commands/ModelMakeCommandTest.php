<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Illuminate\Support\Facades\Artisan;
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

it('generates a new model class', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Entities/Post.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Entities/Post.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates correct fillable fields', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--fillable' => 'title,slug']);

    $file = $this->finder->get($this->modulePath . '/Entities/Post.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates migration file with model', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--migration' => true]);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $migrationFile = $migrations[0];
    $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
    expect($migrations)->toHaveCount(1);
    $this->assertMatchesSnapshot($migrationContent);
    expect($code)->toBe(0);
});

it('generates migration file with model using shortcut option', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-m' => true]);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $migrationFile = $migrations[0];
    $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
    expect($migrations)->toHaveCount(1);
    $this->assertMatchesSnapshot($migrationContent);
    expect($code)->toBe(0);
});

it('generates controller file with model', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--controller' => true]);
    $controllers = $this->finder->allFiles($this->modulePath . '/Http/Controllers');
    $controllerFile = $controllers[1];
    $controllerContent = $this->finder->get($this->modulePath . '/Http/Controllers/' . $controllerFile->getFilename());
    expect($controllers)->toHaveCount(2);
    $this->assertMatchesSnapshot($controllerContent);
    expect($code)->toBe(0);
});

it('generates controller file with model using shortcut option', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true]);

    $controllers = $this->finder->allFiles($this->modulePath . '/Http/Controllers');
    $controllerFile = $controllers[1];
    $controllerContent = $this->finder->get($this->modulePath . '/Http/Controllers/' . $controllerFile->getFilename());
    expect($controllers)->toHaveCount(2);
    $this->assertMatchesSnapshot($controllerContent);
    expect($code)->toBe(0);
});

it('generates controller and migration when both flags are present', function () {
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true, '-m' => true]);

    $controllers = $this->finder->allFiles($this->modulePath . '/Http/Controllers');
    $controllerFile = $controllers[1];
    $controllerContent = $this->finder->get($this->modulePath . '/Http/Controllers/' . $controllerFile->getFilename());
    expect($controllers)->toHaveCount(2);
    $this->assertMatchesSnapshot($controllerContent);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $migrationFile = $migrations[0];
    $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
    expect($migrations)->toHaveCount(1);
    $this->assertMatchesSnapshot($migrationContent);

    expect($code)->toBe(0);
});

it('generates correct migration file name with multiple words model', function () {
    $code = $this->artisan('module:make-model', ['model' => 'ProductDetail', 'module' => 'Blog', '-m' => true]);

    $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
    $migrationFile = $migrations[0];
    $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());

    $this->assertStringContainsString('create_product_details_table', $migrationFile->getFilename());
    $this->assertMatchesSnapshot($migrationContent);
    expect($code)->toBe(0);
});

it('displays error if model already exists', function () {
    $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

    $this->assertStringContainsString('already exists', Artisan::output());
    expect($code)->toBe(E_ERROR);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.model.path', 'Models');

    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Models/Post.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.model.namespace', 'Models');

    $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Entities/Post.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
