<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Tests\BaseTestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

/**
 * Boots the application as for an HTTP request.
 *
 * Application::runningInConsole() reads APP_RUNNING_IN_CONSOLE once and keeps the result,
 * so the variable is set before the application is created.
 */
class ModuleServiceProviderOutsideConsoleTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $configPath;

    protected function setUp(): void
    {
        $_SERVER['APP_RUNNING_IN_CONSOLE'] = 'false';

        // Publish paths are static and keyed by provider class.
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];

        parent::setUp();

        $this->assertFalse($this->app->runningInConsole());

        $this->finder = $this->app['files'];
        $this->createModule();
        $this->configPath = module_path('Blog', config('modules.paths.generator.config.path'));
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');

        parent::tearDown();

        unset($_SERVER['APP_RUNNING_IN_CONSOLE']);
    }

    public function test_cached_config_is_not_read_or_published()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);
        config([
            'blog' => ['name' => 'Cached Blog'],
            // Resolving the module config directory would fail with this path.
            'modules.paths.generator.config.path' => null,
        ]);
        $this->app->instance('config_loaded_from_cache', true);

        $provider = $this->moduleProvider();
        $provider->register();

        $this->assertSame([], ServiceProvider::pathsToPublish($provider::class, 'config'));
        $this->assertSame(['name' => 'Cached Blog'], config('blog'));
    }

    public function test_config_is_merged_without_publishing_when_not_cached()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'settings' => ['theme' => 'dark', 'per_page' => 10]]);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);

        $provider = $this->moduleProvider();
        $provider->register();

        $this->assertSame('Blog', config('blog.name'));
        $this->assertSame('light', config('blog.settings.theme'));
        $this->assertSame(10, config('blog.settings.per_page'));
        $this->assertSame([], ServiceProvider::pathsToPublish($provider::class, 'config'));
    }

    public function test_views_are_loaded_without_publishing()
    {
        $provider = $this->moduleProvider();
        $provider->registerModuleViews();

        $sourcePath = module_path('Blog', config('modules.paths.generator.views.path'));

        $this->assertContains($sourcePath, $this->app['view']->getFinder()->getHints()['blog']);
        $this->assertSame([], ServiceProvider::pathsToPublish($provider::class, 'views'));
        $this->assertSame([], ServiceProvider::pathsToPublish($provider::class, 'blog-module-views'));
    }

    public function test_package_publish_paths_are_skipped()
    {
        $this->assertSame([], ServiceProvider::pathsToPublish(LaravelModulesServiceProvider::class));
    }

    public function test_package_about_info_is_skipped()
    {
        $this->assertSame(0, Artisan::call('about', ['--only' => 'Laravel-Modules']));
        $this->assertStringNotContainsString('Laravel-Modules', Artisan::output());
    }

    #[DefineEnvironment('enableCustomStubs')]
    public function test_programmatic_commands_and_custom_stubs_are_available()
    {
        $this->assertSame(0, Artisan::call('module:list'));
        $this->assertSame('stub-override', Stub::create('/model.stub')->render());
    }

    protected function enableCustomStubs($app): void
    {
        $app['config']->set('modules.stubs.enabled', true);
        $app['config']->set('modules.stubs.path', __DIR__.'/../stubs');
    }

    private function writeModuleConfig(string $file, array $values): void
    {
        $path = $this->configPath.'/'.$file;

        $this->finder->ensureDirectoryExists(dirname($path));
        $this->finder->put($path, '<?php return '.var_export($values, true).';');
    }

    private function moduleProvider(): ModuleServiceProvider
    {
        return new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function registerModuleViews(): void
            {
                $this->registerViews();
            }
        };
    }
}
