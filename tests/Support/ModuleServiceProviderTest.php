<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Support\Fixtures\ModuleConfigReadingProvider;
use PHPUnit\Framework\Attributes\DataProvider;

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

        // Publish paths are static and keyed by provider class; the anonymous provider below keeps its class name across tests.
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_sub_providers_can_read_module_config_in_register()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);

        $this->app->register(new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            protected array $providers = [ModuleConfigReadingProvider::class];
        });

        $this->assertSame('Blog', $this->app->getProvider(ModuleConfigReadingProvider::class)->moduleName);
    }

    public function test_later_providers_can_read_module_config_before_module_boot()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);

        // Testbench has already booted, so calling register() directly keeps the module's boot() pending.
        $this->moduleProvider()->register();
        $reader = $this->app->register(ModuleConfigReadingProvider::class);

        $this->assertSame('Blog', $reader->moduleName);
    }

    public function test_boot_merges_config_when_register_does_not_call_parent()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function register(): void {}
        };

        $provider->booting(fn () => $this->assertNull(config('blog.name')));
        $this->app->register($provider);

        $this->assertSame('Blog', config('blog.name'));
        $this->assertSame(
            [$this->configPath.'/config.php' => config_path('blog.php')],
            ServiceProvider::pathsToPublish($provider::class, 'config')
        );
    }

    public function test_boot_skips_config_already_registered_by_the_base_provider()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $provider = $this->registerModuleConfig();

        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'late_default' => true]);
        $provider->boot();

        $this->assertSame(['name' => 'Blog'], config('blog'));
    }

    public function test_explicitly_registering_config_again_in_boot_preserves_values_and_publish_paths()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'settings' => ['theme' => 'light']]);
        $this->writeModuleConfig('nested/options.php', ['tags' => ['a', 'b'], 'cache' => ['store' => 'file']]);
        config(['blog' => [
            'settings' => ['theme' => 'dark'],
            'nested' => ['options' => ['tags' => ['x'], 'cache' => []]],
        ]]);

        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function boot(): void
            {
                $this->registerConfig();
            }
        };

        $provider->register();
        $values = config('blog');
        $paths = ServiceProvider::pathsToPublish($provider::class, 'config');

        $this->assertSame([
            'name' => 'Blog',
            'settings' => ['theme' => 'dark'],
            'nested' => ['options' => ['tags' => ['x'], 'cache' => []]],
        ], $values);
        $this->assertSame([
            $this->configPath.'/config.php' => config_path('blog.php'),
            $this->configPath.'/nested/options.php' => config_path('blog/nested/options.php'),
        ], $paths);

        $provider->boot();

        $this->assertSame($values, config('blog'));
        $this->assertSame($paths, ServiceProvider::pathsToPublish($provider::class, 'config'));
    }

    public function test_app_config_values_override_module_defaults()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'posts_per_page' => 15]);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);

        // Values already loaded from the app's config/blog.php and config/blog/settings.php (module:publish-config).
        config([
            'blog' => ['posts_per_page' => 99],
            'blog.settings' => ['theme' => 'dark'],
        ]);

        $this->registerModuleConfig();

        $this->assertSame(99, config('blog.posts_per_page'));
        $this->assertSame('dark', config('blog.settings.theme'));
    }

    public function test_module_only_config_keys_stay_available_when_the_app_overrides_others()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'posts_per_page' => 15]);
        $this->writeModuleConfig('settings.php', ['theme' => 'light', 'cache' => true]);
        config([
            'blog' => ['posts_per_page' => 99],
            'blog.settings' => ['theme' => 'dark'],
        ]);

        $this->registerModuleConfig();

        $this->assertSame(99, config('blog.posts_per_page'));
        $this->assertSame('Blog', config('blog.name'));
        $this->assertSame('dark', config('blog.settings.theme'));
        $this->assertTrue(config('blog.settings.cache'));
    }

    public function test_app_only_config_keys_are_kept_when_the_app_overrides_others()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);
        config([
            'blog' => ['name' => 'My Blog', 'featured' => 'news'],
            'blog.settings' => ['theme' => 'dark', 'font' => 'serif'],
        ]);

        $this->registerModuleConfig();

        $this->assertSame('My Blog', config('blog.name'));
        $this->assertSame('news', config('blog.featured'));
        $this->assertSame('dark', config('blog.settings.theme'));
        $this->assertSame('serif', config('blog.settings.font'));
    }

    public function test_nested_config_arrays_merge_with_the_app_value_winning()
    {
        $this->writeModuleConfig('config.php', [
            'cache' => ['enabled' => false, 'store' => 'file', 'ttl' => ['posts' => 60, 'tags' => 120]],
        ]);
        $this->writeModuleConfig('nested/options.php', ['driver' => ['name' => 'file', 'path' => 'blog']]);
        config([
            'blog' => ['cache' => ['enabled' => true, 'ttl' => ['posts' => 5]]],
            'blog.nested.options' => ['driver' => ['name' => 'redis']],
        ]);

        $this->registerModuleConfig();

        $this->assertSame(
            ['enabled' => true, 'store' => 'file', 'ttl' => ['posts' => 5, 'tags' => 120]],
            config('blog.cache')
        );
        $this->assertSame(['name' => 'redis', 'path' => 'blog'], config('blog.nested.options.driver'));
    }

    public function test_a_shorter_app_list_replaces_the_module_list()
    {
        $this->writeModuleConfig('config.php', ['tags' => ['a', 'b', 'c']]);
        $this->writeModuleConfig('settings.php', ['themes' => ['light', 'dark']]);
        config([
            'blog' => ['tags' => ['x']],
            'blog.settings' => ['themes' => ['dark']],
        ]);

        $this->registerModuleConfig();

        $this->assertSame(['x'], config('blog.tags'));
        $this->assertSame(['dark'], config('blog.settings.themes'));
    }

    public function test_an_empty_app_array_clears_the_module_value()
    {
        $this->writeModuleConfig('config.php', ['tags' => ['a', 'b', 'c'], 'cache' => ['store' => 'file']]);
        config(['blog' => ['tags' => [], 'cache' => []]]);

        $this->registerModuleConfig();

        $this->assertSame([], config('blog.tags'));
        $this->assertSame([], config('blog.cache'));
    }

    public function test_a_list_nested_in_an_associative_array_is_replaced_while_the_rest_merges()
    {
        $this->writeModuleConfig('config.php', [
            'cache' => ['enabled' => false, 'store' => 'file', 'tags' => ['posts', 'comments', 'users']],
        ]);
        $this->writeModuleConfig('nested/options.php', [
            'driver' => ['name' => 'file', 'options' => ['path' => 'blog', 'flags' => ['lock', 'gzip']]],
        ]);
        config([
            'blog' => ['cache' => ['enabled' => true, 'tags' => ['posts']]],
            'blog.nested.options' => ['driver' => ['options' => ['flags' => ['lock']]]],
        ]);

        $this->registerModuleConfig();

        $this->assertSame(['enabled' => true, 'store' => 'file', 'tags' => ['posts']], config('blog.cache'));
        $this->assertSame(
            ['name' => 'file', 'options' => ['path' => 'blog', 'flags' => ['lock']]],
            config('blog.nested.options.driver')
        );
    }

    public function test_an_app_list_replaces_a_module_associative_array_and_the_reverse()
    {
        $this->writeModuleConfig('config.php', ['drivers' => ['file' => true], 'channels' => ['mail', 'sms']]);
        config(['blog' => ['drivers' => ['redis'], 'channels' => ['mail' => true]]]);

        $this->registerModuleConfig();

        $this->assertSame(['redis'], config('blog.drivers'));
        $this->assertSame(['mail' => true], config('blog.channels'));
    }

    public function test_a_dedicated_config_file_still_wins_over_config_php_when_the_app_has_config()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog', 'settings' => ['theme' => 'dark', 'per_page' => 10]]);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);
        config(['blog' => ['name' => 'My Blog']]);

        $this->registerModuleConfig();

        // The module's own files keep their order among themselves; only app values win over them.
        $this->assertSame('My Blog', config('blog.name'));
        $this->assertSame('light', config('blog.settings.theme'));
        $this->assertSame(10, config('blog.settings.per_page'));
    }

    public function test_a_dedicated_config_file_wins_over_the_same_key_in_config_php()
    {
        // Written in this order so a directory listing in creation order would load config.php last.
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);
        $this->writeModuleConfig('nested/options.php', ['enabled' => true]);
        $this->writeModuleConfig('config.php', [
            'name' => 'Blog',
            'settings' => ['theme' => 'dark', 'per_page' => 10],
            'nested' => ['options' => ['enabled' => false, 'driver' => 'file']],
        ]);

        $this->registerModuleConfig();

        $this->assertSame('Blog', config('blog.name'));
        $this->assertSame('light', config('blog.settings.theme'));
        $this->assertSame(10, config('blog.settings.per_page'));
        $this->assertTrue(config('blog.nested.options.enabled'));
        $this->assertSame('file', config('blog.nested.options.driver'));
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

    public function test_cached_config_still_registers_publish_paths_in_the_console()
    {
        $this->writeModuleConfig('config.php', ['name' => 'Blog']);
        $this->writeModuleConfig('settings.php', ['theme' => 'light']);
        config(['blog' => ['name' => 'Cached Blog']]);
        $this->app->instance('config_loaded_from_cache', true);

        $provider = $this->registerModuleConfig();

        // vendor:publish runs in the console and must still find the module config files.
        $published = ServiceProvider::pathsToPublish($provider::class, 'config');
        $this->assertSame(config_path('blog.php'), $published[$this->configPath.'/config.php']);
        $this->assertSame(config_path('blog/settings.php'), $published[$this->configPath.'/settings.php']);

        // Cached values are left untouched.
        $this->assertSame(['name' => 'Cached Blog'], config('blog'));
    }

    public function test_views_are_published_in_the_console()
    {
        $provider = $this->moduleProvider();
        $provider->registerModuleViews();

        $sourcePath = module_path('Blog', config('modules.paths.generator.views.path'));

        $this->assertSame(
            [$sourcePath => resource_path('views/modules/blog')],
            ServiceProvider::pathsToPublish($provider::class, 'blog-module-views')
        );
    }

    #[DataProvider('bladeResolutionTiming')]
    public function test_module_boot_registers_its_component_namespace(bool $resolveBeforeBoot): void
    {
        config(['modules.namespace' => 'Nwidart\\Modules\\Tests\\Support\\Fixtures']);

        if ($resolveBeforeBoot) {
            $this->app->make('blade.compiler');
        }

        $this->moduleProvider()->boot();

        $this->assertSame($resolveBeforeBoot, $this->app->resolved('blade.compiler'));
        $this->assertSame('Module badge', Blade::render('<x-blog::lifecycle-badge />'));
    }

    public static function bladeResolutionTiming(): array
    {
        return [
            'after module boot' => [false],
            'before module boot' => [true],
        ];
    }

    private function writeModuleConfig(string $file, array $values): void
    {
        $path = $this->configPath.'/'.$file;

        $this->finder->ensureDirectoryExists(dirname($path));
        $this->finder->put($path, '<?php return '.var_export($values, true).';');
    }

    private function registerModuleConfig(): ModuleServiceProvider
    {
        $provider = $this->moduleProvider();
        $provider->register();

        return $provider;
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
