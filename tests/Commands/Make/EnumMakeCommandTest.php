<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class EnumMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_enum_class()
    {
        $code = $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Enums/MyEnum.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_enum_class_can_override_with_force_option()
    {
        $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog']);
        $code = $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Enums/MyEnum.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Enums/MyEnum.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_enum_in_sub_namespace_in_correct_folder()
    {
        $code = $this->artisan('module:make-enum', ['name' => 'Api\\MyEnum', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Enums/Api/MyEnum.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_enum_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-enum', ['name' => 'Api\\MyEnum', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Enums/Api/MyEnum.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.enums.path', 'app/Domain/Enums');

        $code = $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/app/Domain/Enums/MyEnum.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Domain\\Enums;', $file);
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.enums.namespace', 'Support\\Enums');

        $code = $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Enums/MyEnum.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Support\\Enums;', $file);
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_derives_the_namespace_from_a_path_in_a_custom_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'src/');
        $this->app['config']->set('modules.paths.generator.enums.path', 'src/Domain/Enums');

        $code = $this->artisan('module:make-enum', ['name' => 'MyEnum', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/src/Domain/Enums/MyEnum.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Domain\\Enums;', $file);
        $this->assertSame(0, $code);
    }
}
