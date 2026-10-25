<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ClassMakeCommandTest extends BaseTestCase
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
        $this->modulePath = $this->getModuleAppPath();

    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_class()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Classes/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_class_can_override_with_force_option()
    {
        $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog']);
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Classes/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_class_can_use_invoke_option()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--invokable' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Classes/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_class_can_use_suffix_option()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--suffix' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Classes/DemoClass.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_class_use_type_option()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--type' => 'contract']);

        $this->assertTrue(is_file($this->modulePath.'/Contracts/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Classes/Demo.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_class_in_sub_namespace_in_correct_folder()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Api\\Demo', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Classes/Api/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_class_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Api\\Demo', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Classes/Api/Demo.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_type_folder_as_namespace()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--type' => 'service']);

        $file = $this->finder->get($this->modulePath.'/Services/Demo.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Services;', $file);
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_namespace_configured_under_the_plural_config_key()
    {
        // config/config.php names the key "services", not "service".
        $this->app['config']->set('modules.paths.generator.services.namespace', 'Support\\Services');

        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--type' => 'service']);

        $file = $this->finder->get($this->modulePath.'/Services/Demo.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Support\\Services;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_namespace_configured_under_a_singular_config_key()
    {
        // config/config.php names the key "repository", not "repositories".
        $this->app['config']->set('modules.paths.generator.repository.namespace', 'Data\\Repositories');

        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--type' => 'repository']);

        $file = $this->finder->get($this->modulePath.'/Repositories/Demo.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Data\\Repositories;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_configured_type_namespace()
    {
        // The singular key is still read when the plural one is not set.
        $this->app['config']->set('modules.paths.generator.service.namespace', 'Support\\Services');

        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--type' => 'service']);

        $file = $this->finder->get($this->modulePath.'/Services/Demo.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Support\\Services;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_derives_the_type_namespace_from_a_custom_class_path()
    {
        $this->app['config']->set('modules.paths.generator.class.path', 'app/Domain/Classes');

        $code = $this->artisan('module:make-class', ['name' => 'Demo', 'module' => 'Blog', '--type' => 'contract']);

        $file = $this->finder->get($this->modulePath.'/Domain/Contracts/Demo.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Domain\\Contracts;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_keeps_the_class_name_when_it_contains_classes()
    {
        $code = $this->artisan('module:make-class', ['name' => 'Classes', 'module' => 'Blog', '--type' => 'service']);

        $this->assertTrue(is_file($this->modulePath.'/Services/Classes.php'));
        $this->assertSame(0, $code);
    }
}
