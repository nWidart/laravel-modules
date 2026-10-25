<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RouteProviderMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_service_provider_class()
    {
        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $this->assertTrue(is_file($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($path);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/SuperProviders/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.provider.namespace', 'SuperProviders');

        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
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

        $file = $this->finder->get($this->modulePath.'/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_overwrite_file(): void
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);
        $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);
        $file = $this->finder->get($this->modulePath.'/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_custom_controller_namespace(): void
    {
        $this->app['config']->set('modules.paths.generator.controller.path', 'Base/Http/Controllers');
        $this->app['config']->set('modules.paths.generator.provider.path', 'Base/Providers');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);
        $file = $this->finder->get($this->getModuleBasePath().'/Base/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_does_not_map_web_routes_when_the_web_routes_stub_is_removed(): void
    {
        $this->removeStubFiles('routes/web');

        $file = $this->generateRouteProvider();

        $this->assertStringNotContainsString('mapWebRoutes', $file);
        $this->assertStringContainsString('$this->mapApiRoutes();', $file);
        $this->assertMatchesSnapshot($file);
    }

    public function test_it_does_not_map_api_routes_when_the_api_routes_stub_is_removed(): void
    {
        $this->removeStubFiles('routes/api');

        $file = $this->generateRouteProvider();

        $this->assertStringNotContainsString('mapApiRoutes', $file);
        $this->assertStringContainsString('$this->mapWebRoutes();', $file);
        $this->assertMatchesSnapshot($file);
    }

    public function test_it_maps_no_routes_when_both_routes_stubs_are_removed(): void
    {
        $this->removeStubFiles('routes/web', 'routes/api');

        $file = $this->generateRouteProvider();

        $this->assertStringNotContainsString('mapWebRoutes', $file);
        $this->assertStringNotContainsString('mapApiRoutes', $file);
        $this->assertMatchesSnapshot($file);
    }

    public function test_it_maps_no_routes_when_the_stub_files_config_is_null(): void
    {
        $this->app['config']->set('modules.stubs.files', null);

        $file = $this->generateRouteProvider();

        $this->assertStringNotContainsString('mapWebRoutes', $file);
        $this->assertStringNotContainsString('mapApiRoutes', $file);
    }

    public function test_it_maps_no_routes_when_routes_generation_is_disabled(): void
    {
        $this->app['config']->set('modules.paths.generator.routes.generate', false);

        $file = $this->generateRouteProvider();

        $this->assertStringNotContainsString('mapWebRoutes', $file);
        $this->assertStringNotContainsString('mapApiRoutes', $file);
    }

    private function removeStubFiles(string ...$stubs): void
    {
        $files = $this->app['config']->get('modules.stubs.files');

        foreach ($stubs as $stub) {
            unset($files[$stub]);
        }

        $this->app['config']->set('modules.stubs.files', $files);
    }

    /**
     * Generate the provider, check that it is valid and cleanly formatted PHP, and return it.
     */
    private function generateRouteProvider(): string
    {
        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';

        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);

        $this->assertSame(0, $code);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1', $output, $lint);
        $this->assertSame(0, $lint, implode("\n", $output));

        $file = $this->finder->get($path);

        $this->assertStringNotContainsString('%%', $file);
        $this->assertStringNotContainsString("\n\n\n", $file);
        $this->assertDoesNotMatchRegularExpression('/[ \t]+$/m', $file);

        return $file;
    }
}
