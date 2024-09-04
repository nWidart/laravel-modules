<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class EnumMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();

    }

    public function tearDown(): void
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
}
