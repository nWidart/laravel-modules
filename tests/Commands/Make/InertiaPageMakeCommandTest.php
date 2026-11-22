<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;

class InertiaPageMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

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

    public function test_it_generates_a_vue_inertia_page_by_default()
    {
        $code = $this->artisan('module:make-inertia-page', ['name' => 'Index', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Index.vue'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_vue_inertia_page_with_correct_content()
    {
        $code = $this->artisan('module:make-inertia-page', ['name' => 'Index', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/resources/js/Pages/Index.vue');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_vue_inertia_page_with_vue_flag()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Index',
            'module' => 'Blog',
            '--vue' => true,
        ]);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Index.vue'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_react_inertia_page()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Index',
            'module' => 'Blog',
            '--react' => true,
        ]);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Index.jsx'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_react_inertia_page_with_correct_content()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Index',
            'module' => 'Blog',
            '--react' => true,
        ]);

        $file = $this->finder->get($this->modulePath.'/resources/js/Pages/Index.jsx');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_studly_cases_the_page_name()
    {
        $code = $this->artisan('module:make-inertia-page', ['name' => 'my-page', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/MyPage.vue'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_page_in_a_subdirectory()
    {
        $code = $this->artisan('module:make-inertia-page', ['name' => 'Contacts/Index', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Contacts/Index.vue'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_react_page_in_a_subdirectory()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Contacts/Index',
            'module' => 'Blog',
            '--react' => true,
        ]);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Contacts/Index.jsx'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_svelte_inertia_page()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Index',
            'module' => 'Blog',
            '--svelte' => true,
        ]);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Index.svelte'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_svelte_inertia_page_with_correct_content()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Index',
            'module' => 'Blog',
            '--svelte' => true,
        ]);

        $file = $this->finder->get($this->modulePath.'/resources/js/Pages/Index.svelte');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_svelte_page_in_a_subdirectory()
    {
        $code = $this->artisan('module:make-inertia-page', [
            'name' => 'Contacts/Index',
            'module' => 'Blog',
            '--svelte' => true,
        ]);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Contacts/Index.svelte'));
        $this->assertSame(0, $code);
    }

    #[DataProvider('conflictingFrontends')]
    public function test_it_rejects_more_than_one_frontend_option(array $options, string $named)
    {
        $before = $this->moduleFiles();

        $code = $this->artisan('module:make-inertia-page', ['name' => 'Index', 'module' => 'Blog'] + $options);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            "The {$named} options cannot be used together. Pass only one of --vue, --react or --svelte.",
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame($before, $this->moduleFiles());
    }

    public function test_it_uses_the_configured_frontend_without_a_frontend_option()
    {
        $this->app['config']->set('modules.inertia.frontend', 'svelte');

        $code = $this->artisan('module:make-inertia-page', ['name' => 'Index', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/resources/js/Pages/Index.svelte'));
        $this->assertSame(0, $code);
    }

    /**
     * @return array<string, array{array<string, bool>, string}>
     */
    public static function conflictingFrontends(): array
    {
        return [
            'react and svelte' => [['--react' => true, '--svelte' => true], '--react and --svelte'],
            'vue and react' => [['--vue' => true, '--react' => true], '--vue and --react'],
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
