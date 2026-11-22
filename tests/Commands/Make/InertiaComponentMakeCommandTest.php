<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InertiaComponentMakeCommandTest extends BaseTestCase
{
    private Filesystem $finder;

    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_vue_component_by_default()
    {
        $code = $this->artisan('module:make-inertia-component', ['name' => 'Button', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Components/Button.vue'));
        $this->assertSame(0, $code);
    }

    #[DataProvider('frontends')]
    public function test_it_generates_a_component_for_the_given_frontend(string $option, string $file)
    {
        $code = $this->artisan('module:make-inertia-component', ['name' => 'Button', 'module' => 'Blog', $option => true]);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Components/'.$file));
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_configured_frontend_without_a_frontend_option()
    {
        $this->app['config']->set('modules.inertia.frontend', 'react');

        $code = $this->artisan('module:make-inertia-component', ['name' => 'Button', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Components/Button.jsx'));
        $this->assertSame(0, $code);
    }

    #[DataProvider('conflictingFrontends')]
    public function test_it_rejects_more_than_one_frontend_option(array $options, string $named)
    {
        $before = $this->moduleFiles();

        $code = $this->artisan('module:make-inertia-component', ['name' => 'Button', 'module' => 'Blog'] + $options);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            "The {$named} options cannot be used together. Pass only one of --vue, --react or --svelte.",
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame($before, $this->moduleFiles());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function frontends(): array
    {
        return [
            'vue' => ['--vue', 'Button.vue'],
            'react' => ['--react', 'Button.jsx'],
            'svelte' => ['--svelte', 'Button.svelte'],
        ];
    }

    /**
     * @return array<string, array{array<string, bool>, string}>
     */
    public static function conflictingFrontends(): array
    {
        return [
            'react and svelte' => [['--react' => true, '--svelte' => true], '--react and --svelte'],
            'vue and svelte' => [['--vue' => true, '--svelte' => true], '--vue and --svelte'],
            'all three' => [['--svelte' => true, '--vue' => true, '--react' => true], '--vue, --react and --svelte'],
        ];
    }

    /**
     * @return list<string>
     */
    private function moduleFiles(): array
    {
        return array_map(
            fn ($file) => $file->getPathname(),
            $this->finder->allFiles($this->modulePath)
        );
    }
}
