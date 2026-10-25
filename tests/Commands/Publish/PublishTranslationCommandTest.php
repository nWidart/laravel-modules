<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;

class PublishTranslationCommandTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    /**
     * Directories this test may create in the shared testbench skeleton, mapped to whether they existed before.
     * A leftover resources/lang changes app()->langPath() for every later test.
     *
     * @var array<string, bool>
     */
    private array $preExistingDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];

        foreach ([resource_path('lang'), lang_path('modules')] as $directory) {
            $this->preExistingDirectories[$directory] = is_dir($directory);
        }

        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        foreach ($this->preExistingDirectories as $directory => $existed) {
            $this->finder->deleteDirectory($existed ? $directory.'/blog' : $directory);
        }

        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_published_module_translations()
    {
        $code = $this->artisan('module:publish-translation', ['module' => 'Blog']);

        $this->assertDirectoryExists(lang_path('modules/blog'));
        $this->assertSame(0, $code);
    }

    public function test_it_does_not_create_resources_lang_when_the_app_uses_base_lang()
    {
        $this->assertDirectoryDoesNotExist(resource_path('lang'));
        $this->assertSame(base_path('lang'), lang_path());

        $this->artisan('module:publish-translation', ['module' => 'Blog']);

        // Creating resources/lang would switch app()->langPath() away from lang/ on the next boot.
        $this->assertDirectoryDoesNotExist(resource_path('lang'));
    }

    public function test_published_translations_are_loaded_by_the_module_service_provider()
    {
        $this->putModuleTranslation('Hello from the module');

        $this->artisan('module:publish-translation', ['module' => 'Blog']);

        $publishedFile = lang_path('modules/blog/en/messages.php');
        $this->assertFileExists($publishedFile);

        // Customise the published copy, as an app would, so we can tell which directory the provider loads.
        $this->finder->put($publishedFile, "<?php\n\nreturn ['greeting' => 'Hello from the app'];\n");

        $this->registerBlogTranslations();

        $this->assertSame('Hello from the app', __('blog::messages.greeting'));
    }

    public function test_translations_published_to_resources_lang_modules_still_load()
    {
        $this->putModuleTranslation('Hello from the module');

        // An app that published to resources/lang/modules has resources/lang, so Laravel resolves langPath() there.
        $this->finder->ensureDirectoryExists(resource_path('lang/modules/blog/en'));
        $this->finder->put(
            resource_path('lang/modules/blog/en/messages.php'),
            "<?php\n\nreturn ['greeting' => 'Hello from resources/lang'];\n"
        );
        $this->app->useLangPath(resource_path('lang'));

        $this->registerBlogTranslations();

        $this->assertSame('Hello from resources/lang', __('blog::messages.greeting'));
    }

    public function test_it_keeps_edited_translations_and_publishes_new_files_when_published_again()
    {
        $this->putModuleTranslation('Hello from the module');
        $this->artisan('module:publish-translation', ['module' => 'Blog']);

        $publishedFile = lang_path('modules/blog/en/messages.php');
        $this->finder->put($publishedFile, "<?php\n\nreturn ['greeting' => 'Hello from the app'];\n");
        $this->putModuleTranslation('Hello again from the module', 'en/new.php');

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-translation', ['module' => 'Blog'])
            ->expectsOutputToContain('already exists')
            ->expectsOutputToContain('Use --force to overwrite them.')
            ->assertSuccessful();

        $this->assertStringContainsString('Hello from the app', $this->finder->get($publishedFile));
        $this->assertStringContainsString('Hello again from the module', $this->finder->get(lang_path('modules/blog/en/new.php')));
    }

    public function test_it_overwrites_edited_translations_with_force()
    {
        $this->putModuleTranslation('Hello from the module');
        $this->artisan('module:publish-translation', ['module' => 'Blog']);

        $publishedFile = lang_path('modules/blog/en/messages.php');
        $this->finder->put($publishedFile, "<?php\n\nreturn ['greeting' => 'Hello from the app'];\n");

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish-translation', ['module' => 'Blog', '--force' => true])
            ->doesntExpectOutputToContain('already exists')
            ->assertSuccessful();

        $this->assertStringContainsString('Hello from the module', $this->finder->get($publishedFile));
    }

    private function putModuleTranslation(string $greeting, string $file = 'en/messages.php'): void
    {
        $moduleLangPath = $this->getModuleBasePath().'/'.config('modules.paths.generator.lang.path');
        $this->finder->ensureDirectoryExists(dirname($moduleLangPath.'/'.$file));
        $this->finder->put(
            $moduleLangPath.'/'.$file,
            "<?php\n\nreturn ['greeting' => '{$greeting}'];\n"
        );
    }

    private function registerBlogTranslations(): void
    {
        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function loadTranslations(): void
            {
                $this->registerTranslations();
            }
        };

        $provider->loadTranslations();
    }
}
