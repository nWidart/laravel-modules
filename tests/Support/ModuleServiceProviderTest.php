<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;

class ModuleServiceProviderTest extends BaseTestCase
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
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->configPath = module_path('Blog', config('modules.paths.generator.config.path'));
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_module_config_values_override_existing_config()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);

        // Values already loaded, e.g. from the app's config/blog.php and config/blog/settings.php.
        config([
            'blog' => ['name' => 'My Blog', 'per_page' => 10],
            'blog.settings' => ['theme' => 'dark', 'cache' => true],
        ]);

        $this->registerModuleConfig();

        // Module values win (deliberate, see PR #2053); keys only the app defines are kept.
        $this->assertSame('Blog', config('blog.name'));
        $this->assertSame(10, config('blog.per_page'));
        $this->assertSame('light', config('blog.settings.theme'));
        $this->assertTrue(config('blog.settings.cache'));
    }

    public function test_config_files_are_published_to_the_path_of_their_config_key()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);
        $this->writeModuleConfig('nested/options.php', ['enabled' => true]);
        $this->writeModuleConfig('blog/extra.php', ['enabled' => true]);

        $provider = $this->registerModuleConfig();

        $published = ServiceProvider::pathsToPublish($provider::class, 'config');

        $this->assertSame(config_path('blog.php'), $published[$this->configPath.'/config.php']);
        $this->assertSame(config_path('blog/settings.php'), $published[$this->configPath.'/settings.php']);
        $this->assertSame(config_path('blog/nested/options.php'), $published[$this->configPath.'/nested/options.php']);
        $this->assertSame(config_path('blog/extra.php'), $published[$this->configPath.'/blog/extra.php']);

        // Never publish to a top-level app config file, which could overwrite e.g. the app's config/settings.php.
        $this->assertNotContains(config_path('settings.php'), $published);
        $this->assertNotContains(config_path('nested/options.php'), $published);
        $this->assertNotContains(config_path('extra.php'), $published);
        $this->assertNotContains(config_path('blog/blog/extra.php'), $published);

        $this->assertSame('Blog', config('blog.name'));
        $this->assertSame('light', config('blog.settings.theme'));
        $this->assertTrue(config('blog.nested.options.enabled'));
        $this->assertTrue(config('blog.extra.enabled'));
    }

    private function writeModuleConfig(string $file, array $values): void
    {
        $path = $this->configPath.'/'.$file;

        $this->finder->ensureDirectoryExists(dirname($path));
        $this->finder->put($path, '<?php return '.var_export($values, true).';');
    }

    private function registerModuleConfig(): ModuleServiceProvider
    {
        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function registerModuleConfig(): void
            {
                $this->registerConfig();
            }
        };

        $provider->registerModuleConfig();

        return $provider;
    }
}
