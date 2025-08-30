<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ProviderMakeCommandTest extends BaseTestCase
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
        $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true]);
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_service_provider()
    {
        $code = $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Providers/MyBlogServiceProvider.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Providers/MyBlogServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_master_service_provider_with_resource_loading()
    {
        $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

        $file = $this->finder->get($this->module_app_path('app/Providers/BlogServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_have_custom_migration_resources_location_paths()
    {
        $this->app['config']->set('modules.paths.generator.migration', 'migrations');
        $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

        $file = $this->finder->get($this->module_app_path('app/Providers/BlogServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

        $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

        $file = $this->finder->get($this->module_path('SuperProviders/BlogServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.provider.namespace', 'SuperProviders');

        $code = $this->artisan('module:make-provider', ['name' => 'BlogServiceProvider', 'module' => 'Blog', '--master' => true]);

        $file = $this->finder->get($this->module_app_path('app/Providers/BlogServiceProvider.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
