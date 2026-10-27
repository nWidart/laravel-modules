<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ConfigMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

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

        // registerConfig() adds publish paths to static properties of ServiceProvider.
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];

        parent::tearDown();
    }

    public function test_it_generates_the_config_file()
    {
        $code = $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/config/stripe.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/config/stripe.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_keeps_the_name_as_given()
    {
        $code = $this->artisan('module:make-config', ['name' => 'payment_gateways', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/config/payment_gateways.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_nested_config_file()
    {
        $code = $this->artisan('module:make-config', ['name' => 'services/stripe', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/config/services/stripe.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_does_not_double_the_php_extension()
    {
        $code = $this->artisan('module:make-config', ['name' => 'stripe.php', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/config/stripe.php'));
        $this->assertFalse(is_file($this->modulePath.'/config/stripe.php.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.config.path', 'settings');

        $code = $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/settings/stripe.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_keeps_an_existing_file_without_force()
    {
        $path = $this->modulePath.'/config/stripe.php';

        $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog']);

        $this->assertSame('edited', $this->finder->get($path));
        $this->assertSame(E_ERROR, $code);
    }

    public function test_it_overwrites_an_existing_file_with_force()
    {
        $path = $this->modulePath.'/config/stripe.php';

        $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-config', ['name' => 'stripe', 'module' => 'Blog', '--force' => true]);

        $this->assertNotSame('edited', $this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_the_module_service_provider_loads_the_generated_config_file()
    {
        $this->artisan('module:make-config', ['name' => 'services/stripe', 'module' => 'Blog']);

        $this->assertFalse(config()->has('blog.services.stripe'));

        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function registerModuleConfig(): void
            {
                $this->registerConfig();
            }
        };
        $provider->registerModuleConfig();

        $this->assertSame([], config('blog.services.stripe'));
    }
}
