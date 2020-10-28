<?php

namespace Nwidart\Modules\Tests\Commands;

use Spatie\Snapshots\MatchesSnapshots;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Contracts\RepositoryInterface;

class ComponentViewCommandTest extends BaseTestCase
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
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    /** @test */
    public function it_generates_the_component_view()
    {
        $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);
        $this->assertTrue(is_file($this->modulePath . '/Resources/views/components/blog.blade.php'));
        $this->assertSame(0, $code);
    }
    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);
        $file = $this->finder->get($this->modulePath .  '/Resources/views/components/blog.blade.php');
        $this->assertTrue(str_contains($file,'<div>'));
        $this->assertSame(0, $code);
    }

     /** @test */
     public function it_can_change_the_default_namespace()
     {
         $this->app['config']->set('modules.paths.generator.component-view.path', 'Resources/views/components/newDirectory');

         $code = $this->artisan('module:make-component-view', ['name' => 'Blog', 'module' => 'Blog']);

         $file = $this->finder->get($this->modulePath .  '/Resources/views/components/newDirectory/blog.blade.php');

         $this->assertTrue(str_contains($file,'<div>'));
         $this->assertSame(0, $code);
     }
}
