<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class MiddlewareMakeCommandTest extends BaseTestCase
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
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_middleware_class()
    {
        $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Middleware/SomeMiddleware.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Middleware/SomeMiddleware.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.filter.path', 'Middleware');

        $this->artisan('module:make-middleware', ['name' => 'SomeMiddleware', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Middleware/SomeMiddleware.php');

        $this->assertMatchesSnapshot($file);
    }
}
