<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class GenerateMiddlewareCommandTest extends BaseTestCase
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

        $this->assertTrue(str_contains($file, 'class SomeMiddleware'));
        $this->assertTrue(str_contains($file, 'public function handle(Request $request, Closure $next)'));
    }
}
