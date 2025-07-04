<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActionMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_generates_new_action_class()
    {
        $code = $this->artisan('module:make-action', ['name' => 'MyAction', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Actions/MyAction.php')));
        $this->assertSame(0, $code);
    }

    public function test_generates_new_action_class_with_force_option()
    {
        $this->artisan('module:make-action', ['name' => 'MyAction', 'module' => 'Blog']);
        $code = $this->artisan('module:make-action', ['name' => 'MyAction', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->module_app_path('app/Actions/MyAction.php')));
        $this->assertSame(0, $code);
    }

    public function test_generates_new_action_class_with_invoke_option()
    {
        $code = $this->artisan('module:make-action', ['name' => 'MyAction', 'module' => 'Blog', '--invokable' => true]);

        $this->assertTrue(is_file($this->module_app_path('app/Actions/MyAction.php')));
        $this->assertSame(0, $code);
    }

    public function test_generates_correct_file_with_content()
    {
        $code = $this->artisan('module:make-action', ['name' => 'MyAction', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Actions/MyAction.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_generates_action_in_sub_folder()
    {
        $code = $this->artisan('module:make-action', ['name' => 'Api\\MyAction', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Actions/Api/MyAction.php')));
        $file = $this->finder->get($this->module_app_path('app/Actions/Api/MyAction.php'));
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
