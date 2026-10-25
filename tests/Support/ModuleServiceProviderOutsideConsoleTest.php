<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;

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

        parent::setUp();

        $this->assertFalse($this->app->runningInConsole());

        $this->finder = $this->app['files'];
        $this->createModule();
        $this->configPath = module_path('Blog', config('modules.paths.generator.config.path'));

        // Publish paths are static and keyed by provider class; the anonymous provider below keeps its class name across tests.
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];
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
        config(['blog' => ['name' => 'Cached Blog']]);
        $this->app->instance('config_loaded_from_cache', true);

        $provider = $this->moduleProvider();
        $provider->registerModuleConfig();

        $this->assertSame([], ServiceProvider::pathsToPublish($provider::class, 'config'));
        $this->assertSame(['name' => 'Cached Blog'], config('blog'));
    }

    public function test_config_is_merged_without_publishing_when_not_cached()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'settings' => ['theme' => 'dark', 'per_page' => 10]]);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);

        $provider = $this->moduleProvider();
        $provider->registerModuleConfig();

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

            public function registerModuleConfig(): void
            {
                $this->registerConfig();
            }

            public function registerModuleViews(): void
            {
                $this->registerViews();
            }
        };
    }
}
