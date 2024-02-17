<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->repository = $this->app[RepositoryInterface::class];
    $this->activator = $this->app[ActivatorInterface::class];
});

afterEach(function () {
    $this->finder->deleteDirectory($this->modulePath);
    if ($this->finder->isDirectory(base_path('modules/ModuleName'))) {
        $this->finder->deleteDirectory(base_path('modules/ModuleName'));
    }
    $this->activator->reset();
});

it('generates module', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    expect($this->modulePath)->toBeDirectory();
    expect($code)->toBe(0);
});

it('generates module folders', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    foreach (config('modules.paths.generator') as $directory) {
        expect($this->modulePath . '/' . $directory['path'])->toBeDirectory();
    }
    expect($code)->toBe(0);
});

it('generates module files', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    foreach (config('modules.stubs.files') as $file) {
        $path = base_path('modules/Blog') . '/' . $file;
        expect($this->finder->exists($path))->toBeTrue("[$file] does not exists");
    }
    $path = base_path('modules/Blog') . '/module.json';
    expect($this->finder->exists($path))->toBeTrue('[module.json] does not exists');
    $this->assertMatchesSnapshot($this->finder->get($path));
    expect($code)->toBe(0);
});

it('generates web route file', function () {
    $files = $this->app['modules']->config('stubs.files');
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $path = $this->modulePath . '/' . $files['routes/web'];

    $this->assertMatchesSnapshot($this->finder->get($path));
    expect($code)->toBe(0);
});

it('generates api route file', function () {
    $files = $this->app['modules']->config('stubs.files');
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $path = $this->modulePath . '/' . $files['routes/api'];

    $this->assertMatchesSnapshot($this->finder->get($path));
    expect($code)->toBe(0);
});

it('generates vite file', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $path = $this->modulePath . '/' . $this->app['modules']->config('stubs.files.vite');

    $this->assertMatchesSnapshot($this->finder->get($path));
    expect($code)->toBe(0);
});

it('generates module resources', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    expect($code)->toBe(0);
});

it('generates correct composerjson file', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $file = $this->finder->get($this->modulePath . '/composer.json');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates module folder using studly case', function () {
    $code = $this->artisan('module:make', ['name' => ['ModuleName']]);

    expect($this->finder->exists(base_path('modules/ModuleName')))->toBeTrue();
    expect($code)->toBe(0);
});

it('generates module namespace using studly case', function () {
    $code = $this->artisan('module:make', ['name' => ['ModuleName']]);

    $file = $this->finder->get(base_path('modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates a plain module with no resources', function () {
    $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

    $path = base_path('modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php';
    expect($this->finder->exists($path))->toBeFalse();

    $path = base_path('modules/ModuleName') . '/Http/Controllers/ModuleNameController.php';
    expect($this->finder->exists($path))->toBeFalse();

    $path = base_path('modules/ModuleName') . '/Database/Seeders/ModuleNameDatabaseSeeder.php';
    expect($this->finder->exists($path))->toBeFalse();

    expect($code)->toBe(0);
});

it('generates a plain module with no files', function () {
    $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

    foreach (config('modules.stubs.files') as $file) {
        $path = base_path('modules/ModuleName') . '/' . $file;
        expect($this->finder->exists($path))->toBeFalse("[$file] exists");
    }
    $path = base_path('modules/ModuleName') . '/module.json';
    expect($this->finder->exists($path))->toBeTrue('[module.json] does not exists');
    expect($code)->toBe(0);
});

it('generates plain module with no service provider in modulejson file', function () {
    $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

    $path = base_path('modules/ModuleName') . '/module.json';
    $content = json_decode($this->finder->get($path));

    expect($content->providers)->toHaveCount(0);
    expect($code)->toBe(0);
});

it('outputs error when module exists', function () {
    $this->artisan('module:make', ['name' => ['Blog']]);
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $output = Artisan::output();
    $expected = 'ERROR  Module [Blog] already exists!';

    expect(Str::contains($output, $expected))->toBeTrue();

    expect($code)->toBe(E_ERROR);
});

it('still generates module if it exists using force flag', function () {
    $this->artisan('module:make', ['name' => ['Blog']]);
    $code = $this->artisan('module:make', ['name' => ['Blog'], '--force' => true]);

    $output = Artisan::output();

    $notExpected = 'Module [Blog] already exist!
';
    $this->assertNotEquals($notExpected, $output);
    expect(Str::contains($output, 'Module [Blog] created successfully.'))->toBeTrue();
    expect($code)->toBe(0);
});

it('can generate module with old config format', function () {
    $this->app['config']->set('modules.paths.generator', [
        'assets' => 'Assets',
        'config' => 'Config',
        'command' => 'Console',
        'event' => 'Events',
        'listener' => 'Listeners',
        'migration' => 'Database/Migrations',
        'factory' => 'Database/factories',
        'model' => 'Entities',
        'repository' => 'Repositories',
        'seeder' => 'Database/Seeders',
        'controller' => 'Http/Controllers',
        'filter' => 'Http/Middleware',
        'request' => 'Http/Requests',
        'provider' => 'Providers',
        'lang' => 'Resources/lang',
        'views' => 'Resources/views',
        'policies' => false,
        'rules' => false,
        'test' => 'Tests',
        'jobs' => 'Jobs',
        'emails' => 'Emails',
        'notifications' => 'Notifications',
        'resource' => false,
    ]);

    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    expect($this->modulePath . '/Assets')->toBeDirectory();
    expect($this->modulePath . '/Emails')->toBeDirectory();
    $this->assertFileDoesNotExist($this->modulePath . '/Rules');
    $this->assertFileDoesNotExist($this->modulePath . '/Policies');
    expect($code)->toBe(0);
});

it('can ignore some folders to generate with old format', function () {
    $this->app['config']->set('modules.paths.generator.assets', false);
    $this->app['config']->set('modules.paths.generator.emails', false);

    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $this->assertFileDoesNotExist($this->modulePath . '/Assets');
    $this->assertFileDoesNotExist($this->modulePath . '/Emails');
    expect($code)->toBe(0);
});

it('can ignore some folders to generate with new format', function () {
    $this->app['config']->set('modules.paths.generator.assets', ['path' => 'Assets', 'generate' => false]);
    $this->app['config']->set('modules.paths.generator.emails', ['path' => 'Emails', 'generate' => false]);

    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $this->assertFileDoesNotExist($this->modulePath . '/Assets');
    $this->assertFileDoesNotExist($this->modulePath . '/Emails');
    expect($code)->toBe(0);
});

it('can ignore resource folders to generate', function () {
    $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]);
    $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Providers', 'generate' => false]);
    $this->app['config']->set('modules.paths.generator.controller', ['path' => 'Http/Controllers', 'generate' => false]);

    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    $this->assertFileDoesNotExist($this->modulePath . '/Database/Seeders');
    $this->assertFileDoesNotExist($this->modulePath . '/Providers');
    $this->assertFileDoesNotExist($this->modulePath . '/Http/Controllers');
    expect($code)->toBe(0);
});

it('generates enabled module', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    expect($this->repository->isEnabled('Blog'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generates disabled module with disabled flag', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog'], '--disabled' => true]);

    expect($this->repository->isDisabled('Blog'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generes module with new provider location', function () {
    $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

    $code = $this->artisan('module:make', ['name' => ['Blog']]);

    expect($this->modulePath . '/Base/Providers')->toBeDirectory();
    $file = $this->finder->get($this->modulePath . '/module.json');
    $this->assertMatchesSnapshot($file);
    $file = $this->finder->get($this->modulePath . '/composer.json');
    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('generates web module with resources', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog'], '--web' => true]);

    $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    expect($code)->toBe(0);
});

it('generates api module with resources', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog'], '--api' => true]);

    $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    expect($code)->toBe(0);
});

it('generates web module with resources when adding more than one option', function () {
    $code = $this->artisan('module:make', ['name' => ['Blog'], '--api' => true,'--plain'=>true]);

    $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
    expect($this->finder->exists($path))->toBeTrue();
    $this->assertMatchesSnapshot($this->finder->get($path));

    expect($code)->toBe(0);
});
