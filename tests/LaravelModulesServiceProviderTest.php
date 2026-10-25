<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Translation\Translator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Support\Stub;
use Orchestra\Testbench\Attributes\DefineEnvironment;
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

    public function test_it_does_not_auto_discover_module_translations_by_default()
    {
        $this->createModule('Blog');

        $langPath = $this->moduleLangPath('Blog');
        $translator = $this->resolveFreshTranslator();

        $this->assertNotContains($langPath, $translator->getLoader()->jsonPaths());
        $this->assertNotSame($langPath, $translator->getLoader()->namespaces()['blog'] ?? null);
    }

    #[WithConfig('modules.auto-discover.translations', true, defer: false)]
    public function test_it_auto_discovers_module_translations_when_enabled()
    {
        $this->createModule('Blog');

        $langPath = $this->moduleLangPath('Blog');
        $translator = $this->resolveFreshTranslator();

        $this->assertContains($langPath, $translator->getLoader()->jsonPaths());
        $this->assertSame($langPath, $translator->getLoader()->namespaces()['blog'] ?? null);
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

    private function resolveFreshTranslator(): Translator
    {
        $this->app->forgetInstance('translator');

        return $this->app->make('translator');
    }
}
