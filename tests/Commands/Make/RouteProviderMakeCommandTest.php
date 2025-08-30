<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RouteProviderMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_service_provider_class()
    {
        $path = $this->module_app_path('Providers/RouteServiceProvider.php');
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $this->assertTrue(is_file($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $path = $this->module_app_path('Providers/RouteServiceProvider.php');
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($path);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($this->module_path('SuperProviders/RouteServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.provider.namespace', 'SuperProviders');

        $path = $this->module_app_path('Providers/RouteServiceProvider.php');
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($path);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_overwrite_route_file_names()
    {
        $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');
        $this->app['config']->set('modules.stubs.files.routes/api', 'SuperRoutes/api.php');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);

        $file = $this->finder->get($this->module_app_path('Providers/RouteServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_overwrite_file(): void
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);
        $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);
        $file = $this->finder->get($this->module_app_path('Providers/RouteServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_custom_controller_namespace(): void
    {
        $this->app['config']->set('modules.paths.generator.controller.path', 'Base/Http/Controllers');
        $this->app['config']->set('modules.paths.generator.provider.path', 'Base/Providers');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);
        $file = $this->finder->get($this->module_path('Base/Providers/RouteServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
