<?php

namespace Nwidart\Modules\tests\Commands;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Tests\BaseTestCase;

class ModuleGeneratorTest extends BaseTestCase
{
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
            $this->assertTrue(is_dir($this->modulePath . '/' . $directory));
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
    }

    /** @test */
    public function it_generates_module_resources()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $path = base_path('modules/Blog') . '/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));

        $path = base_path('modules/Blog') . '/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));

        $path = base_path('modules/Blog') . '/Database/Seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
    }

    /** @test */
    public function it_generates_correct_composerjson_file()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);

        $file = $this->finder->get($this->modulePath . '/composer.json');

        $this->assertEquals($this->getExpectedComposerJson(), $file);
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

        $this->assertTrue(str_contains($file, 'namespace Modules\ModuleName\Providers;'));
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

    private function getExpectedComposerJson()
    {
        return <<<TEXT
{
    "name": "nwidart/blog",
    "description": "",
    "authors": [
        {
            "name": "Nicolas Widart",
            "email": "n.widart@gmail.com"
        }
    ],
    "autoload": {
        "psr-4": {
            "Modules\\\Blog\\\": ""
        }
    }
}

TEXT;
    }
}
