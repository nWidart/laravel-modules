<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class GenerateRouteServiceProviderTest extends BaseTestCase
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
    public function it_generates_a_new_service_provider_class()
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Providers/RouteServiceProvider.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Providers/RouteServiceProvider.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The root namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected \$rootUrlNamespace = 'Modules\Blog\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @param  Router \$router
     * @return void
     */
    public function before(Router \$router)
    {
        //
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map(Router \$router)
    {
        // if (!app()->routesAreCached()) {
        //    require __DIR__ . '/Http/routes.php';
        // }
    }
}

TEXT;
    }
}
