<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
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
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();

    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_controller_class()
    {
        $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Http/Controllers/MyController.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_appends_controller_to_name_if_not_present()
    {
        $code = $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Http/Controllers/MyController.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_appends_controller_to_class_name_if_not_present()
    {
        $code = $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_plain_controller()
    {
        $code = $this->artisan('module:make-controller', [
            'controller' => 'MyController',
            'module' => 'Blog',
            '--plain' => true,
        ]);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_an_api_controller()
    {
        $code = $this->artisan('module:make-controller', [
            'controller' => 'MyController',
            'module' => 'Blog',
            '--api' => true,
        ]);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_an_invokable_controller()
    {
        $code = $this->artisan('module:make-controller', [
            'controller' => 'MyController',
            'module' => 'Blog',
            '--invokable' => true,
        ]);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.controller.path', 'Controllers');

        $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.controller.namespace', 'Controllers');

        $code = $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_controller_in_sub_namespace_in_correct_folder()
    {
        $code = $this->artisan('module:make-controller', ['controller' => 'Api\\MyController', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Http/Controllers/Api/MyController.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_controller_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-controller', ['controller' => 'Api\\MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Http/Controllers/Api/MyController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
