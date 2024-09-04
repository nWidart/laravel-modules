<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ComponentClassMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_the_component_class()
    {
        $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);
        $this->assertTrue(is_file($this->modulePath.'/View/Components/Blog.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_the_component_view_from_component_class_command()
    {
        $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);
        $file = $this->finder->get($this->getModuleBasePath().'/resources/views/components/blog.blade.php');
        $this->assertTrue(str_contains($file, '<div>'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);
        $file = $this->finder->get($this->modulePath.'/View/Components/Blog.php');
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.component-class.path', 'View/Components/newDirectory');

        $code = $this->artisan('module:make-component', ['name' => 'Blog', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/View/Components/newDirectory/Blog.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
