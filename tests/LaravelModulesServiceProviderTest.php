<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Translation\Translator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
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
