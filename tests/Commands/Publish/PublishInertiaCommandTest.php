<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;

class PublishInertiaCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private const QUESTION = 'resources/js/app.js already exists. Overwrite?';

    private const SSR_QUESTION = 'resources/js/app.js, resources/js/ssr.js and resources/js/inertia-pages.js already exist. Overwrite?';

    private const EXISTING = "// existing app.js\n";

    private Filesystem $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->finder->deleteDirectory(resource_path('js'));
    }

    protected function tearDown(): void
    {
        $this->finder->deleteDirectory(resource_path('js'));
        parent::tearDown();
    }

    public function test_it_publishes_the_vue_app_by_default()
    {
        $code = $this->artisan('module:publish-inertia');

        $this->assertSame(0, $code);
        $this->assertMatchesSnapshot($this->appJs());
    }

    public function test_it_publishes_the_react_app()
    {
        $code = $this->artisan('module:publish-inertia', ['--react' => true]);

        $this->assertSame(0, $code);
        $this->assertMatchesSnapshot($this->appJs());
    }

    public function test_it_publishes_the_svelte_app()
    {
        $code = $this->artisan('module:publish-inertia', ['--svelte' => true]);

        $this->assertSame(0, $code);
        $this->assertMatchesSnapshot($this->appJs());
    }

    public function test_the_published_apps_mount_only_with_the_page_data_of_inertia_v3()
    {
        // Inertia v3 renders the page data in a <script data-page="app" type="application/json"> tag
        $guard = "const hasPageData = document.querySelector('script[data-page=\"app\"][type=\"application/json\"]')\n"
            ."\n"
            ."if (el && hasPageData) {\n"
            .'    createInertiaApp(';

        foreach (['--vue', '--react', '--svelte'] as $frontend) {
            $this->artisan('module:publish-inertia', [$frontend => true, '--force' => true]);

            $this->assertStringContainsString("document.getElementById('app')", $this->appJs(), $frontend);
            $this->assertStringContainsString($guard, $this->appJs(), $frontend);
            $this->assertStringNotContainsString('dataset.page', $this->appJs(), $frontend);
        }
    }

    public function test_the_published_svelte_app_mounts_with_the_svelte_5_api()
    {
        $this->artisan('module:publish-inertia', ['--svelte' => true]);

        $this->assertStringContainsString("import { mount } from 'svelte'", $this->appJs());
        $this->assertStringContainsString('mount(App, { target: el, props })', $this->appJs());
        $this->assertStringNotContainsString('new App(', $this->appJs());
    }

    public function test_the_published_react_app_has_no_jsx()
    {
        // The app is written to app.js, and Vite parses JSX only in .jsx files
        $this->artisan('module:publish-inertia', ['--react' => true]);

        $this->assertStringContainsString("import { createElement } from 'react'", $this->appJs());
        $this->assertStringContainsString('createRoot(el).render(createElement(App, props))', $this->appJs());
        $this->assertStringNotContainsString('<App', $this->appJs());
    }

    #[DataProvider('conflictingFrontends')]
    public function test_it_rejects_more_than_one_frontend_option(array $options, string $named)
    {
        $code = $this->artisan('module:publish-inertia', $options);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            "The {$named} options cannot be used together. Pass only one of --vue, --react or --svelte.",
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertFalse($this->finder->exists(resource_path('js/app.js')));
    }

    public function test_it_rejects_more_than_one_frontend_option_with_force_and_keeps_an_existing_app()
    {
        $this->createExistingAppJs();

        $code = $this->artisan('module:publish-inertia', ['--react' => true, '--svelte' => true, '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertSame(self::EXISTING, $this->appJs());
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

    public function test_it_overwrites_an_existing_app_when_the_user_confirms()
    {
        $this->createExistingAppJs();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia')
            ->expectsConfirmation(self::QUESTION, 'yes')
            ->expectsOutputToContain('Published Inertia app.js to resources/js/app.js')
            ->assertSuccessful();

        $this->assertStringContainsString("from '@inertiajs/vue3'", $this->appJs());
    }

    public function test_it_keeps_an_existing_app_when_the_user_declines()
    {
        $this->createExistingAppJs();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia')
            ->expectsConfirmation(self::QUESTION, 'no')
            ->expectsOutputToContain('Nothing was written. resources/js/app.js was left unchanged.')
            ->doesntExpectOutputToContain('Published Inertia app.js')
            ->assertExitCode(E_ERROR);

        $this->assertSame(self::EXISTING, $this->appJs());
    }

    public function test_it_keeps_an_existing_app_and_errors_without_interaction()
    {
        $this->createExistingAppJs();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia', ['--no-interaction' => true])
            ->doesntExpectOutputToContain(self::QUESTION)
            ->expectsOutputToContain('resources/js/app.js already exists. Use --force to overwrite.')
            ->assertExitCode(E_ERROR);

        $this->assertSame(self::EXISTING, $this->appJs());
    }

    public function test_it_overwrites_an_existing_app_with_force_without_asking()
    {
        $this->createExistingAppJs();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia', ['--force' => true])
            ->doesntExpectOutputToContain(self::QUESTION)
            ->expectsOutputToContain('Published Inertia app.js to resources/js/app.js')
            ->assertSuccessful();

        $this->assertStringContainsString("from '@inertiajs/vue3'", $this->appJs());
    }

    public function test_it_publishes_the_vue_ssr_files_by_default()
    {
        $code = $this->artisan('module:publish-inertia', ['--ssr' => true]);

        $this->assertSame(0, $code);
        $this->assertMatchesSnapshot($this->appJs());
        $this->assertMatchesSnapshot($this->jsFile('ssr.js'));
        $this->assertMatchesSnapshot($this->jsFile('inertia-pages.js'));
    }

    public function test_it_publishes_the_react_ssr_files()
    {
        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--react' => true]);

        $this->assertSame(0, $code);
        $this->assertMatchesSnapshot($this->appJs());
        $this->assertMatchesSnapshot($this->jsFile('ssr.js'));
        $this->assertMatchesSnapshot($this->jsFile('inertia-pages.js'));
    }

    public function test_it_publishes_the_svelte_ssr_files()
    {
        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--svelte' => true]);

        $this->assertSame(0, $code);
        $this->assertMatchesSnapshot($this->appJs());
        $this->assertMatchesSnapshot($this->jsFile('ssr.js'));
        $this->assertMatchesSnapshot($this->jsFile('inertia-pages.js'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function frontends(): array
    {
        return [
            'vue' => ['--vue', '@inertiajs/vue3', 'vue'],
            'react' => ['--react', '@inertiajs/react', 'jsx'],
            'svelte' => ['--svelte', '@inertiajs/svelte', 'svelte'],
        ];
    }

    #[DataProvider('frontends')]
    public function test_the_ssr_entry_creates_the_app_at_the_top_level(string $frontend, string $package, string $extension)
    {
        // @inertiajs/vite wraps createInertiaApp in the SSR server only when the call is a top-level statement
        $this->artisan('module:publish-inertia', ['--ssr' => true, $frontend => true]);

        $ssr = $this->jsFile('ssr.js');

        $this->assertStringContainsString("import { createInertiaApp } from '{$package}'", $ssr);
        $this->assertStringContainsString("import { resolvePage } from './inertia-pages'", $ssr);
        $this->assertMatchesRegularExpression('/^createInertiaApp\(\{$/m', $ssr);
        $this->assertStringNotContainsString('if (', $ssr);
        $this->assertStringNotContainsString('document', $ssr);
        $this->assertStringNotContainsString('setup', $ssr);
    }

    #[DataProvider('frontends')]
    public function test_the_ssr_app_keeps_the_page_data_guard_and_lets_inertia_mount_or_hydrate(string $frontend, string $package, string $extension)
    {
        $this->artisan('module:publish-inertia', ['--ssr' => true, $frontend => true]);

        $app = $this->appJs();
        $guard = "if (document.querySelector('script[data-page=\"app\"][type=\"application/json\"]')) {\n"
            .'    createInertiaApp({';

        $this->assertStringContainsString("import { createInertiaApp } from '{$package}'", $app);
        $this->assertStringContainsString("import { resolvePage } from './inertia-pages'", $app);
        $this->assertStringContainsString($guard, $app);
        $this->assertStringContainsString('resolve: resolvePage,', $app);
        $this->assertDoesNotMatchRegularExpression('/\bsetup\s*[(:]/', $app);
        $this->assertStringNotContainsString('createApp', $app);
        $this->assertStringNotContainsString('createRoot', $app);
        $this->assertStringNotContainsString('mount(', $app);
    }

    #[DataProvider('frontends')]
    public function test_the_ssr_page_resolver_prefers_module_pages(string $frontend, string $package, string $extension)
    {
        $this->artisan('module:publish-inertia', ['--ssr' => true, $frontend => true]);

        $pages = $this->jsFile('inertia-pages.js');

        $this->assertStringContainsString("import.meta.glob('./Pages/**/*.{$extension}')", $pages);
        $this->assertStringContainsString("import.meta.glob('/Modules/*/resources/js/Pages/**/*.{$extension}')", $pages);
        $this->assertStringContainsString(
            "const page = modulePages[`/Modules/\${module}/resources/js/Pages/\${rest.join('/')}.{$extension}`]\n"
            ."        ?? appPages[`./Pages/\${name}.{$extension}`]\n",
            $pages,
        );
        $this->assertStringNotContainsString('$', str_replace('${', '', $pages));
    }

    public function test_it_prints_the_ssr_next_steps()
    {
        $this->artisan('module:publish-inertia', ['--ssr' => true]);

        $output = preg_replace('/\s+/', ' ', Artisan::output());

        $this->assertStringContainsString('Published Inertia app.js, ssr.js and inertia-pages.js to resources/js', $output);
        $this->assertStringContainsString('npm install -D @inertiajs/vite', $output);
        $this->assertStringContainsString('Add inertia() from @inertiajs/vite to the vite.config.js plugins, after laravel(...)', $output);
        $this->assertStringContainsString('"vite build && vite build --ssr"', $output);
        $this->assertStringContainsString('php artisan inertia:start-ssr', $output);
    }

    public function test_it_prints_no_ssr_next_steps_without_ssr()
    {
        $this->artisan('module:publish-inertia');

        $this->assertStringNotContainsString('inertia:start-ssr', Artisan::output());
        $this->assertFalse($this->finder->exists(resource_path('js/ssr.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/inertia-pages.js')));
    }

    public function test_it_writes_no_ssr_file_and_errors_without_interaction_when_some_exist()
    {
        $this->createExistingJsFile('ssr.js');
        $this->createExistingJsFile('inertia-pages.js');

        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--no-interaction' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js/ssr.js and resources/js/inertia-pages.js already exist. Use --force to overwrite.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertFalse($this->finder->exists(resource_path('js/app.js')));
        $this->assertSame(self::EXISTING, $this->jsFile('ssr.js'));
        $this->assertSame(self::EXISTING, $this->jsFile('inertia-pages.js'));
    }

    public function test_it_writes_no_ssr_file_and_errors_without_interaction_when_app_js_exists()
    {
        $this->createExistingAppJs();

        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--no-interaction' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js/app.js already exists. Use --force to overwrite.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame(self::EXISTING, $this->appJs());
        $this->assertFalse($this->finder->exists(resource_path('js/ssr.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/inertia-pages.js')));
    }

    public function test_it_keeps_the_existing_ssr_files_when_the_user_declines()
    {
        $this->createExistingSsrFiles();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia', ['--ssr' => true])
            ->expectsConfirmation(self::SSR_QUESTION, 'no')
            ->expectsOutputToContain('Nothing was written.')
            ->doesntExpectOutputToContain('Published Inertia')
            ->assertExitCode(E_ERROR);

        $this->assertExistingSsrFilesUnchanged();
    }

    public function test_it_overwrites_the_existing_ssr_files_when_the_user_confirms()
    {
        $this->createExistingSsrFiles();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia', ['--ssr' => true])
            ->expectsConfirmation(self::SSR_QUESTION, 'yes')
            ->expectsOutputToContain('Published Inertia app.js, ssr.js and inertia-pages.js')
            ->assertSuccessful();

        $this->assertSsrFilesPublished();
    }

    public function test_it_overwrites_the_existing_ssr_files_with_force_without_asking()
    {
        $this->createExistingSsrFiles();

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-inertia', ['--ssr' => true, '--force' => true])
            ->doesntExpectOutputToContain('Overwrite?')
            ->expectsOutputToContain('Published Inertia app.js, ssr.js and inertia-pages.js')
            ->assertSuccessful();

        $this->assertSsrFilesPublished();
    }

    public function test_it_rejects_more_than_one_frontend_option_with_ssr_and_writes_nothing()
    {
        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--vue' => true, '--react' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'The --vue and --react options cannot be used together.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertFalse($this->finder->exists(resource_path('js/app.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/ssr.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/inertia-pages.js')));
    }

    public function test_it_rejects_more_than_one_frontend_option_with_ssr_and_force_and_keeps_existing_files()
    {
        $this->createExistingSsrFiles();

        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--react' => true, '--svelte' => true, '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertExistingSsrFilesUnchanged();
    }

    public function test_it_writes_nothing_with_ssr_and_force_when_a_destination_is_a_directory()
    {
        $this->createExistingAppJs();
        $this->finder->ensureDirectoryExists(resource_path('js/ssr.js'));

        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js/ssr.js is a directory. Nothing was written.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame(self::EXISTING, $this->appJs());
        $this->assertTrue($this->finder->isDirectory(resource_path('js/ssr.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/inertia-pages.js')));
    }

    public function test_it_writes_nothing_with_ssr_and_force_when_an_existing_destination_is_not_writable()
    {
        $this->skipUnlessPermissionsApply();
        $this->createExistingSsrFiles();
        chmod(resource_path('js/inertia-pages.js'), 0444);

        try {
            $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--force' => true]);
        } finally {
            chmod(resource_path('js/inertia-pages.js'), 0644);
        }

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js/inertia-pages.js is not writable. Nothing was written.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertExistingSsrFilesUnchanged();
    }

    public function test_it_writes_nothing_when_resources_js_is_not_writable()
    {
        $this->skipUnlessPermissionsApply();
        $this->finder->ensureDirectoryExists(resource_path('js'));
        chmod(resource_path('js'), 0555);

        try {
            $code = $this->artisan('module:publish-inertia', ['--ssr' => true]);
        } finally {
            chmod(resource_path('js'), 0755);
        }

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js is not writable. Nothing was written.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame([], $this->finder->files(resource_path('js')));
    }

    public function test_it_writes_nothing_with_ssr_and_force_when_a_destination_is_a_dangling_symlink()
    {
        $this->skipUnlessSymlinksWork();
        $this->finder->ensureDirectoryExists(resource_path('js'));
        symlink('missing.js', resource_path('js/ssr.js'));

        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js/ssr.js is a symbolic link. Replace it with a regular file or remove it. Nothing was written.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame('missing.js', readlink(resource_path('js/ssr.js')));
        $this->assertFalse(file_exists(resource_path('js/missing.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/app.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/inertia-pages.js')));
    }

    public function test_it_writes_nothing_with_ssr_and_force_when_a_destination_links_to_another()
    {
        $this->skipUnlessSymlinksWork();
        $this->finder->ensureDirectoryExists(resource_path('js'));
        symlink('inertia-pages.js', resource_path('js/ssr.js'));

        $code = $this->artisan('module:publish-inertia', ['--ssr' => true, '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString(
            'resources/js/ssr.js is a symbolic link. Replace it with a regular file or remove it. Nothing was written.',
            preg_replace('/\s+/', ' ', Artisan::output())
        );
        $this->assertSame('inertia-pages.js', readlink(resource_path('js/ssr.js')));
        $this->assertFalse(file_exists(resource_path('js/inertia-pages.js')));
        $this->assertFalse($this->finder->exists(resource_path('js/app.js')));
    }

    private function skipUnlessPermissionsApply(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
            $this->markTestSkipped('File permissions do not restrict writes for root or on Windows.');
        }
    }

    private function skipUnlessSymlinksWork(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Creating symlinks on Windows needs extra privileges.');
        }
    }

    private function createExistingSsrFiles(): void
    {
        foreach (['app.js', 'ssr.js', 'inertia-pages.js'] as $file) {
            $this->createExistingJsFile($file);
        }
    }

    private function assertExistingSsrFilesUnchanged(): void
    {
        foreach (['app.js', 'ssr.js', 'inertia-pages.js'] as $file) {
            $this->assertSame(self::EXISTING, $this->jsFile($file), $file);
        }
    }

    private function assertSsrFilesPublished(): void
    {
        $this->assertStringContainsString("from '@inertiajs/vue3'", $this->appJs());
        $this->assertStringContainsString("from '@inertiajs/vue3'", $this->jsFile('ssr.js'));
        $this->assertStringContainsString('export function resolvePage(name)', $this->jsFile('inertia-pages.js'));
    }

    private function createExistingJsFile(string $file): void
    {
        $this->finder->ensureDirectoryExists(resource_path('js'));
        $this->finder->put(resource_path('js/'.$file), self::EXISTING);
    }

    private function jsFile(string $file): string
    {
        return $this->finder->get(resource_path('js/'.$file));
    }

    private function createExistingAppJs(): void
    {
        $this->finder->ensureDirectoryExists(resource_path('js'));
        $this->finder->put(resource_path('js/app.js'), self::EXISTING);
    }

    private function appJs(): string
    {
        return $this->finder->get(resource_path('js/app.js'));
    }
}
