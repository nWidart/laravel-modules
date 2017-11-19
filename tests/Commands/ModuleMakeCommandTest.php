<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Support\Facades\Artisan;
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

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        if ($this->finder->isDirectory(base_path('modules/ModuleName'))) {
            $this->finder->deleteDirectory(base_path('modules/ModuleName'));
        }
        parent::tearDown();
    }

    /** @test */
    public function it_generates_module()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertTrue(is_dir($this->modulePath));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_folders()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        foreach (config('modules.paths.generator') as $directory) {
            $this->assertTrue(is_dir($this->modulePath . '/' . $directory['path']));
        }
    }

    /** @test */
    public function it_generates_module_files()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        foreach (config('modules.stubs.files') as $file) {
            $path = base_path('modules/Blog') . '/' . $file;
            $this->assertTrue($this->finder->exists($path), "[$file] does not exists");
        }
        $path = base_path('modules/Blog') . '/module.json';
        $this->assertTrue($this->finder->exists($path), '[module.json] does not exists');
        $this->assertMatchesSnapshot($this->finder->get($path));
    }

    /** @test */
    public function it_generates_route_file()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath . '/' . $this->app['modules']->config('stubs.files.routes');

        $this->assertMatchesSnapshot($this->finder->get($path));
    }

    /** @test */
    public function it_generates_start_php_file()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath . '/' . $this->app['modules']->config('stubs.files.start');

        $this->assertMatchesSnapshot($this->finder->get($path));
    }

    /** @test */
    public function it_generates_module_resources()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));
    }

    /** @test */
    public function it_generates_correct_composerjson_file()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $file = $this->finder->get($this->modulePath . '/composer.json');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_module_folder_using_studly_case()
    {
        $this->artisan('module:make', ['name' => ['ModuleName']]);

        $this->assertTrue($this->finder->exists(base_path('modules/ModuleName')));
    }

    /** @test */
    public function it_generates_module_namespace_using_studly_case()
    {
        $this->artisan('module:make', ['name' => ['ModuleName']]);

        $file = $this->finder->get(base_path('modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_a_plain_module_with_no_resources()
    {
        $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php';
        $this->assertFalse($this->finder->exists($path));

        $path = base_path('modules/ModuleName') . '/Http/Controllers/ModuleNameController.php';
        $this->assertFalse($this->finder->exists($path));

        $path = base_path('modules/ModuleName') . '/Database/Seeders/ModuleNameDatabaseSeeder.php';
        $this->assertFalse($this->finder->exists($path));
    }

    /** @test */
    public function it_generates_a_plain_module_with_no_files()
    {
        $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        foreach (config('modules.stubs.files') as $file) {
            $path = base_path('modules/ModuleName') . '/' . $file;
            $this->assertFalse($this->finder->exists($path), "[$file] exists");
        }
        $path = base_path('modules/ModuleName') . '/module.json';
        $this->assertTrue($this->finder->exists($path), '[module.json] does not exists');
    }

    /** @test */
    public function it_generates_plain_module_with_no_service_provider_in_modulejson_file()
    {
        $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName') . '/module.json';
        $content = json_decode($this->finder->get($path));

        $this->assertCount(0, $content->providers);
    }

    /** @test */
    public function it_outputs_error_when_module_exists()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->artisan('module:make', ['name' => ['Blog']]);

        $expected = 'Module [Blog] already exist!
';
        $this->assertEquals($expected, Artisan::output());
    }

    /** @test */
    public function it_still_generates_module_if_it_exists_using_force_flag()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->artisan('module:make', ['name' => ['Blog'], '--force' => true]);

        $output = Artisan::output();

        $notExpected = 'Module [Blog] already exist!
';
        $this->assertNotEquals($notExpected, $output);
        $this->assertTrue(str_contains($output, 'Module [Blog] created successfully.'));
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

        $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertTrue(is_dir($this->modulePath . '/Assets'));
        $this->assertTrue(is_dir($this->modulePath . '/Emails'));
        $this->assertFalse(is_dir($this->modulePath . '/Rules'));
        $this->assertFalse(is_dir($this->modulePath . '/Policies'));
    }

    /** @test */
    public function it_can_ignore_some_folders_to_generate_with_old_format()
    {
        $this->app['config']->set('modules.paths.generator.assets', false);
        $this->app['config']->set('modules.paths.generator.emails', false);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertFalse(is_dir($this->modulePath . '/Assets'));
        $this->assertFalse(is_dir($this->modulePath . '/Emails'));
    }

    /** @test */
    public function it_can_ignore_some_folders_to_generate_with_new_format()
    {
        $this->app['config']->set('modules.paths.generator.assets', ['path' => 'Assets', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.emails', ['path' => 'Emails', 'generate' => false]);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertFalse(is_dir($this->modulePath . '/Assets'));
        $this->assertFalse(is_dir($this->modulePath . '/Emails'));
    }
}
