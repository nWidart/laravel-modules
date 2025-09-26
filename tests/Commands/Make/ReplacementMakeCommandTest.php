<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ReplacementMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /** @var \Illuminate\Filesystem\Filesystem */
    private $finder;

    /** @var string */
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

    public function test_it_generates_a_new_replacement_class()
    {
        $code = $this->artisan('module:make-replacement', ['name' => 'Demo', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Console/Replacements/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_replacement_class_can_override_with_force_option()
    {
        $this->artisan('module:make-replacement', ['name' => 'Demo', 'module' => 'Blog']);
        $code = $this->artisan('module:make-replacement', ['name' => 'Demo', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Console/Replacements/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-replacement', ['name' => 'Demo', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Console/Replacements/Demo.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_replacement_class_in_sub_namespace_in_correct_folder()
    {
        $code = $this->artisan('module:make-replacement', ['name' => 'Api\\Demo', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Console/Replacements/Api/Demo.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_replacement_class_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-replacement', ['name' => 'Api\\Demo', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Console/Replacements/Api/Demo.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
