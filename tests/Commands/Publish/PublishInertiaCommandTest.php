<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class PublishInertiaCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private const QUESTION = 'resources/js/app.js already exists. Overwrite?';

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

    public function test_the_published_apps_mount_with_the_page_data_of_inertia_v2_and_v3()
    {
        // Inertia v2 renders the page data in the root's data-page attribute, v3 in a <script data-page="app"> tag
        $guard = "const hasPageData = el?.dataset.page || document.querySelector('script[data-page=\"app\"][type=\"application/json\"]')\n"
            ."\n"
            ."if (el && hasPageData) {\n"
            .'    createInertiaApp(';

        foreach (['--vue', '--react', '--svelte'] as $frontend) {
            $this->artisan('module:publish-inertia', [$frontend => true, '--force' => true]);

            $this->assertStringContainsString("document.getElementById('app')", $this->appJs(), $frontend);
            $this->assertStringContainsString($guard, $this->appJs(), $frontend);
        }
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
