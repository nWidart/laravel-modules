<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ComponentViewMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_the_component_view()
    {
        $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);
        $this->assertTrue(is_file($this->module_path('resources/views/components/blog.blade.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);
        $file = $this->finder->get($this->module_path('resources/views/components/blog.blade.php'));
        $this->assertTrue(str_contains($file, '<div>'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.component-view.path', 'resources/views/components/newDirectory');

        $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('resources/views/components/newDirectory/blog.blade.php'));

        $this->assertTrue(str_contains($file, '<div>'));
        $this->assertSame(0, $code);
    }
}
