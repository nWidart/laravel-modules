<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ScopeMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_scope_class()
    {
        $code = $this->artisan('module:make-scope', ['name' => 'MyScope', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('Models/Scopes/MyScope.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_scope_class_can_override_with_force_option()
    {
        $this->artisan('module:make-scope', ['name' => 'MyScope', 'module' => 'Blog']);
        $code = $this->artisan('module:make-scope', ['name' => 'MyScope', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->module_app_path('Models/Scopes/MyScope.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-scope', ['name' => 'MyScope', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('Models/Scopes/MyScope.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_scope_in_sub_namespace_in_correct_folder()
    {
        $code = $this->artisan('module:make-scope', ['name' => 'Api/MyScope', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('Models/Scopes/Api/MyScope.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_scope_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-scope', ['name' => 'Api/MyScope', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('Models/Scopes/Api/MyScope.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
