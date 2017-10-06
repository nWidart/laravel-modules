<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class TestMakeCommandTest extends BaseTestCase
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

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_test_class()
    {
        $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Tests/EloquentPostRepositoryTest.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Tests/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.test.path', 'SuperTests');

        $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/SuperTests/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
    }
}
