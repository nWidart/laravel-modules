<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ModuleMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    /**
     * @var ActivatorInterface
     */
    private $activator;
    /**
     * @var RepositoryInterface
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->repository = $this->app[RepositoryInterface::class];
        $this->activator = $this->app[ActivatorInterface::class];
    }

    public function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
        if ($this->finder->isDirectory(base_path('modules/ModuleName'))) {
            $this->finder->deleteDirectory(base_path('modules/ModuleName'));
        }
        $this->activator->reset();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_module()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryExists($this->modulePath);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_folders()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        foreach (config('modules.paths.generator') as $directory) {
            $this->assertDirectoryExists($this->modulePath . '/' . $directory['path']);
        }
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_files()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        foreach (config('modules.stubs.files') as $file) {
            $path = base_path('modules/Blog') . '/' . $file;
            $this->assertTrue($this->finder->exists($path), "[$file] does not exists");
        }
        $path = base_path('modules/Blog') . '/module.json';
        $this->assertTrue($this->finder->exists($path), '[module.json] does not exists');
        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_web_route_file()
    {
        $files = $this->app['modules']->config('stubs.files');
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath . '/' . $files['routes/web'];

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_api_route_file()
    {
        $files = $this->app['modules']->config('stubs.files');
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath . '/' . $files['routes/api'];

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_webpack_file()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath . '/' . $this->app['modules']->config('stubs.files.webpack');

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_correct_composerjson_file()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $file = $this->finder->get($this->modulePath . '/composer.json');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_folder_using_studly_case()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName']]);

        $this->assertTrue($this->finder->exists(base_path('modules/ModuleName')));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_namespace_using_studly_case()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName']]);

        $file = $this->finder->get(base_path('modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_a_plain_module_with_no_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php';
        $this->assertFalse($this->finder->exists($path));

        $path = base_path('modules/ModuleName') . '/Http/Controllers/ModuleNameController.php';
        $this->assertFalse($this->finder->exists($path));

        $path = base_path('modules/ModuleName') . '/Database/Seeders/ModuleNameDatabaseSeeder.php';
        $this->assertFalse($this->finder->exists($path));

        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_a_plain_module_with_no_files()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        foreach (config('modules.stubs.files') as $file) {
            $path = base_path('modules/ModuleName') . '/' . $file;
            $this->assertFalse($this->finder->exists($path), "[$file] exists");
        }
        $path = base_path('modules/ModuleName') . '/module.json';
        $this->assertTrue($this->finder->exists($path), '[module.json] does not exists');
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_plain_module_with_no_service_provider_in_modulejson_file()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName') . '/module.json';
        $content = json_decode($this->finder->get($path));

        $this->assertCount(0, $content->providers);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_outputs_error_when_module_exists()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $expected = 'Module [Blog] already exist!
';
        $this->assertEquals($expected, Artisan::output());
        $this->assertSame(E_ERROR, $code);
    }

    /** @test */
    public function it_still_generates_module_if_it_exists_using_force_flag()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--force' => true]);

        $output = Artisan::output();

        $notExpected = 'Module [Blog] already exist!
';
        $this->assertNotEquals($notExpected, $output);
        $this->assertTrue(Str::contains($output, 'Module [Blog] created successfully.'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_module_with_old_config_format()
    {
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

        $this->assertDirectoryExists($this->modulePath . '/Assets');
        $this->assertDirectoryExists($this->modulePath . '/Emails');
        $this->assertDirectoryNotExists($this->modulePath . '/Rules');
        $this->assertDirectoryNotExists($this->modulePath . '/Policies');
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_ignore_some_folders_to_generate_with_old_format()
    {
        $this->app['config']->set('modules.paths.generator.assets', false);
        $this->app['config']->set('modules.paths.generator.emails', false);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryNotExists($this->modulePath . '/Assets');
        $this->assertDirectoryNotExists($this->modulePath . '/Emails');
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_ignore_some_folders_to_generate_with_new_format()
    {
        $this->app['config']->set('modules.paths.generator.assets', ['path' => 'Assets', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.emails', ['path' => 'Emails', 'generate' => false]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryNotExists($this->modulePath . '/Assets');
        $this->assertDirectoryNotExists($this->modulePath . '/Emails');
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_ignore_resource_folders_to_generate()
    {
        $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Providers', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.controller', ['path' => 'Http/Controllers', 'generate' => false]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryNotExists($this->modulePath . '/Database/Seeders');
        $this->assertDirectoryNotExists($this->modulePath . '/Providers');
        $this->assertDirectoryNotExists($this->modulePath . '/Http/Controllers');
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_enabled_module()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertTrue($this->repository->isEnabled('Blog'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_disabled_module_with_disabled_flag()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--disabled' => true]);

        $this->assertTrue($this->repository->isDisabled('Blog'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generes_module_with_new_provider_location()
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryExists($this->modulePath . '/Base/Providers');
        $file = $this->finder->get($this->modulePath . '/module.json');
        $this->assertMatchesSnapshot($file);
        $file = $this->finder->get($this->modulePath . '/composer.json');
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_web_module_with_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--web' => true]);

        $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }
    /** @test */
    public function it_generates_api_module_with_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--api' => true]);

        $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }
    /** @test */
    public function it_generates_web_module_with_resources_when_adding_more_than_one_option()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--api' => true,'--plain'=>true]);

        $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }
}
