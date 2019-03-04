<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ControllerMakeCommandTest extends BaseTestCase
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

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_controller_class()
    {
        $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Controllers/MyController.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_appends_controller_to_name_if_not_present()
    {
        $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Controllers/MyController.php'));
    }

    /** @test */
    public function it_appends_controller_to_class_name_if_not_present()
    {
        $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_a_plain_controller()
    {
        $this->artisan('module:make-controller', [
            'controller' => 'MyController',
            'module' => 'Blog',
            '--plain' => true,
        ]);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_an_api_controller()
    {
        $this->artisan('module:make-controller', [
            'controller' => 'MyController',
            'module' => 'Blog',
            '--api' => true,
        ]);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.controller.path', 'Controllers');

        $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_can_generate_a_controller_in_sub_namespace_in_correct_folder()
    {
        $this->artisan('module:make-controller', ['controller' => 'Api\\MyController', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Controllers/Api/MyController.php'));
    }

    /** @test */
    public function it_can_generate_a_controller_in_sub_namespace_with_correct_generated_file()
    {
        $this->artisan('module:make-controller', ['controller' => 'Api\\MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/Api/MyController.php');

        $this->assertMatchesSnapshot($file);
    }
}
