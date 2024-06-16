<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class InterfaceMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_interface_class()
    {
        $code = $this->artisan('module:make-interface', ['name' => 'MyInterface', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Interfaces/MyInterface.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_interface_class_can_override_with_force_option()
    {
        $this->artisan('module:make-interface', ['name' => 'MyInterface', 'module' => 'Blog']);
        $code = $this->artisan('module:make-interface', ['name' => 'MyInterface', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Interfaces/MyInterface.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-interface', ['name' => 'MyInterface', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Interfaces/MyInterface.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_interface_in_sub_namespace_in_correct_folder()
    {
        $code = $this->artisan('module:make-interface', ['name' => 'Api\\MyInterface', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Interfaces/Api/MyInterface.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_interface_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-interface', ['name' => 'Api\\MyInterface', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Interfaces/Api/MyInterface.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
