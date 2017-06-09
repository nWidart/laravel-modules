<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class GenerateProviderCommandTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true, ]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_service_provider()
    {
        $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Providers/MyBlogServiceProvider.php'));
    }
    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Providers/MyBlogServiceProvider.php');

        $this->assertEquals($this->expectedPlainContent(), $file);
    }

    /** @test */
    public function it_generates_a_master_service_provider_with_resource_loading()
    {
        $this->artisan('module:make-provider', ['name' => 'MyBlogServiceProvider', 'module' => 'Blog', '--master' => true]);

        $file = $this->finder->get($this->modulePath . '/Providers/MyBlogServiceProvider.php');

        $this->assertTrue(str_contains($file, $this->getExpectedMasterPart()));
    }

    private function expectedPlainContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;

class MyBlogServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected \$defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}

TEXT;
    }

    private function getExpectedMasterPart()
    {
        return <<<TEXT
        \$this->registerTranslations();
        \$this->registerConfig();
        \$this->registerViews();
        \$this->registerFactories();
TEXT;
    }
}
