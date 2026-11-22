<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Nwidart\Modules\Commands\Actions\ListCommand;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Support\Stub;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Attributes\ResolvesLaravel;
use Orchestra\Testbench\Attributes\WithConfig;

class LaravelModulesServiceProviderTest extends BaseTestCase
{
    protected function tearDown(): void
    {
        $repository = $this->app[RepositoryInterface::class];

        if ($repository->has('Blog')) {
            $repository->delete('Blog');
        }

        parent::tearDown();
    }

    public function test_it_binds_modules_key_to_repository_class()
    {
        $this->assertInstanceOf(RepositoryInterface::class, app(RepositoryInterface::class));
        $this->assertInstanceOf(RepositoryInterface::class, app('modules'));
    }

    public function test_it_registers_repository_as_a_shared_instance()
    {
        $this->assertTrue($this->app->isShared(RepositoryInterface::class));
        $this->assertSame(app('modules'), app('modules'));
        $this->assertSame(app('modules'), app(RepositoryInterface::class));
    }

    public function test_shared_repository_follows_configured_modules_path()
    {
        $repository = app(RepositoryInterface::class);

        $this->assertSame(config('modules.paths.modules'), $repository->getPath());

        config(['modules.paths.modules' => base_path('custom-modules')]);

        $this->assertSame(base_path('custom-modules'), app(RepositoryInterface::class)->getPath());
    }

    public function test_it_binds_activator_to_activator_class()
    {
        $this->assertInstanceOf(ActivatorInterface::class, app(ActivatorInterface::class));
    }

    public function test_it_throws_exception_if_config_is_invalid()
    {
        $this->expectException(InvalidActivatorClass::class);

        $this->app['config']->set('modules.activators.file', ['class' => null]);

        app()->forgetInstance(ActivatorInterface::class);

        $this->assertInstanceOf(ActivatorInterface::class, app(ActivatorInterface::class));
    }

    public function test_it_throws_a_clear_exception_for_an_unknown_activator()
    {
        $this->app['config']->set('modules.activator', 'redis');
        app()->forgetInstance(ActivatorInterface::class);

        $this->expectException(InvalidActivatorClass::class);
        $this->expectExceptionMessage('The modules activator [redis] is not defined.');

        app(ActivatorInterface::class);
    }

    public function test_it_throws_a_clear_exception_when_the_activator_class_does_not_exist()
    {
        $this->app['config']->set('modules.activators.file.class', 'App\\Missing\\Activator');
        app()->forgetInstance(ActivatorInterface::class);

        $this->expectException(InvalidActivatorClass::class);
        $this->expectExceptionMessage('The class [App\\Missing\\Activator] of the modules activator [file] does not exist.');

        app(ActivatorInterface::class);
    }

    public function test_it_does_not_auto_discover_module_translations_by_default()
    {
        $this->createModule('Blog');

        $langPath = $this->moduleLangPath('Blog');
        $translator = $this->resolveFreshTranslator();

        $this->assertNotContains($langPath, $translator->getLoader()->jsonPaths());
        $this->assertNotSame($langPath, $translator->getLoader()->namespaces()['blog'] ?? null);
    }

    #[WithConfig('modules.auto-discover.translations', true, defer: false)]
    #[WithConfig('modules.auto-discover.migrations', false, defer: false)]
    public function test_it_honours_published_auto_discovery_flags()
    {
        $this->createModule('Blog');

        $langPath = $this->moduleLangPath('Blog');
        $translator = $this->resolveFreshTranslator();

        $this->assertContains($langPath, $translator->getLoader()->jsonPaths());
        $this->assertSame($langPath, $translator->getLoader()->namespaces()['blog'] ?? null);
        $this->assertNotContains($this->moduleMigrationPath('Blog'), $this->freshMigratorPaths());
    }

    #[WithConfig('modules.auto-discover', [], defer: false)]
    public function test_it_uses_discovery_defaults_for_missing_nested_keys()
    {
        $this->createModule('Blog');

        $this->assertContains($this->moduleMigrationPath('Blog'), $this->freshMigratorPaths());
        $this->assertNotContains($this->moduleLangPath('Blog'), $this->resolveFreshTranslator()->getLoader()->jsonPaths());
    }

    public function test_it_merges_package_defaults_before_configuring_an_already_resolved_translator()
    {
        $this->createModule('Blog');
        $this->setModuleJson('Blog', ['providers' => []]);
        $translator = $this->app['translator'];
        $originalConfig = config('modules');

        config(['modules' => [
            'paths' => $originalConfig['paths'],
            'auto-discover' => ['translations' => true],
        ]]);
        Module::clearResolvedInstance('modules');

        try {
            $this->app->register(LaravelModulesServiceProvider::class, force: true);

            $this->assertSame($this->moduleLangPath('Blog'), $translator->getLoader()->namespaces()['blog'] ?? null);
        } finally {
            config(['modules' => $originalConfig]);
        }
    }

    #[WithConfig('modules.commands', [ListCommand::class], defer: false)]
    public function test_it_registers_only_the_commands_from_published_config()
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('module:list', $commands);
        $this->assertArrayNotHasKey('module:make', $commands);
    }

    #[ResolvesLaravel('resetPublishPaths')]
    public function test_it_registers_publish_paths_about_info_and_commands_in_the_console()
    {
        $this->assertTrue($this->app->runningInConsole());

        $this->assertSame([config_path('modules.php')], array_values(ServiceProvider::pathsToPublish(LaravelModulesServiceProvider::class, 'config')));
        $this->assertSame([base_path('stubs/nwidart-stubs')], array_values(ServiceProvider::pathsToPublish(LaravelModulesServiceProvider::class, 'stubs')));
        $this->assertSame([base_path('vite-module-loader.js')], array_values(ServiceProvider::pathsToPublish(LaravelModulesServiceProvider::class, 'vite')));
        $this->assertSame(0, Artisan::call('module:list'));
        $this->assertSame(0, Artisan::call('about', ['--only' => 'Laravel-Modules']));
        $this->assertStringContainsString('Laravel-Modules', Artisan::output());
    }

    public function test_it_registers_aliases_of_enabled_modules()
    {
        $this->createModule('Blog');
        $this->setModuleJson('Blog', [
            'providers' => [],
            'aliases' => ['ModuleManifestTestBlogAlias' => Str::class],
        ]);

        $this->registerModules();

        $this->assertSame(Str::class, AliasLoader::getInstance()->getAliases()['ModuleManifestTestBlogAlias'] ?? null);
        $this->assertTrue(class_exists('ModuleManifestTestBlogAlias'));
    }

    public function test_it_does_not_register_aliases_of_disabled_modules()
    {
        $this->createModule('Blog');
        $this->setModuleJson('Blog', [
            'providers' => [],
            'aliases' => ['ModuleManifestTestDisabledAlias' => Str::class],
        ]);
        $this->app[RepositoryInterface::class]->find('Blog')->disable();

        $this->registerModules();

        $this->assertArrayNotHasKey('ModuleManifestTestDisabledAlias', AliasLoader::getInstance()->getAliases());
    }

    public function test_module_manifest_cache_is_stored_next_to_the_services_cache_by_default()
    {
        $this->assertSame($this->app->bootstrapPath('cache/modules.php'), $this->cachedModulePath());
    }

    public function test_module_manifest_cache_never_overwrites_a_custom_services_cache_file()
    {
        // The file name does not end in "services.php", so a plain name replacement would return the services cache path.
        $_SERVER['APP_SERVICES_CACHE'] = '/tmp/laravel-cache/services-cache.php';

        try {
            $this->assertSame('/tmp/laravel-cache/modules.php', $this->cachedModulePath());
            $this->assertNotSame($this->app->getCachedServicesPath(), $this->cachedModulePath());
        } finally {
            unset($_SERVER['APP_SERVICES_CACHE']);
        }
    }

    public function test_module_manifest_cache_is_stored_next_to_the_config_cache_in_vapor_maintenance_mode()
    {
        $this->app['config']->set('modules.vapor_maintenance_mode', true);
        $_SERVER['APP_CONFIG_CACHE'] = '/tmp/storage/bootstrap/cache/config.php';

        try {
            $this->assertSame('/tmp/storage/bootstrap/cache/modules.php', $this->cachedModulePath());
        } finally {
            unset($_SERVER['APP_CONFIG_CACHE']);
        }
    }

    public function test_it_uses_package_stubs_when_custom_stubs_are_disabled()
    {
        // A published config sets the custom path before the provider runs.
        $this->app['config']->set('modules.stubs.enabled', false);
        $this->app['config']->set('modules.stubs.path', __DIR__.'/stubs');
        $this->app->getProvider(LaravelModulesServiceProvider::class)->setupStubPath();

        $this->assertSame($this->packageStubsPath(), Stub::getBasePath());

        $this->createModule('Blog');
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertStringNotContainsString('stub-override', $this->generatedModel('Blog', 'Post'));
    }

    #[WithConfig('modules.stubs.path', __DIR__.'/stubs', defer: false)]
    public function test_it_uses_package_stubs_when_custom_stubs_enabled_key_is_missing()
    {
        $this->assertSame($this->packageStubsPath(), Stub::getBasePath());
    }

    #[DefineEnvironment('enableCustomStubs')]
    public function test_it_uses_custom_stubs_path_when_custom_stubs_are_enabled()
    {
        $this->assertSame(__DIR__.'/stubs', Stub::getBasePath());

        $this->createModule('Blog');
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertSame('stub-override', $this->generatedModel('Blog', 'Post'));
    }

    #[WithConfig('modules.stubs.enabled', true, defer: false)]
    #[WithConfig('modules.stubs.path', null, defer: false)]
    public function test_it_falls_back_to_package_stubs_when_custom_stubs_path_is_empty()
    {
        $this->assertSame($this->packageStubsPath(), Stub::getBasePath());
    }

    /**
     * Testbench runs this after the providers register and before they boot.
     */
    protected function enableCustomStubs($app): void
    {
        $app['config']->set('modules.stubs.enabled', true);
        $app['config']->set('modules.stubs.path', __DIR__.'/stubs');
    }

    protected function resetPublishPaths(): void
    {
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];
    }

    /**
     * Run the provider's module registration again, so it picks up modules created by the test.
     */
    private function registerModules(): void
    {
        $this->app->forgetInstance(ModuleManifest::class);

        $provider = $this->app->getProvider(LaravelModulesServiceProvider::class);

        (fn () => $this->registerModules())->call($provider);
    }

    private function cachedModulePath(): string
    {
        $provider = $this->app->getProvider(LaravelModulesServiceProvider::class);

        return (fn () => $this->getCachedModulePath())->call($provider);
    }

    private function setModuleJson(string $moduleName, array $attributes): void
    {
        $json = $this->app[RepositoryInterface::class]->find($moduleName)->json();

        foreach ($attributes as $key => $value) {
            $json->set($key, $value);
        }

        $json->save();
    }

    private function packageStubsPath(): string
    {
        return realpath(__DIR__.'/../src/Commands/stubs');
    }

    private function generatedModel(string $moduleName, string $model): string
    {
        return $this->app['files']->get($this->getModuleAppPath($moduleName).'/Models/'.$model.'.php');
    }

    private function moduleLangPath(string $moduleName): string
    {
        return $this->app[RepositoryInterface::class]
            ->find($moduleName)
            ->getExtraPath(config('modules.paths.generator.lang.path'));
    }

    private function moduleMigrationPath(string $moduleName): string
    {
        return module_path($moduleName, config('modules.paths.generator.migration.path'));
    }

    private function freshMigratorPaths(): array
    {
        $this->app->forgetInstance('migrator');

        return $this->app['migrator']->paths();
    }

    private function resolveFreshTranslator(): Translator
    {
        $this->app->forgetInstance('translator');

        return $this->app->make('translator');
    }
}
