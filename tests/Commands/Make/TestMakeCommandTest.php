<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
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
     * @var ActivatorInterface
     */
    private $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        $this->activator->reset();
        parent::tearDown();
    }

    public function test_it_generates_a_new_unit_test_class()
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_path('tests/Unit/EloquentPostRepositoryTest.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_feature_test_class()
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $this->assertTrue(is_file($this->module_path('tests/Feature/EloquentPostRepositoryTest.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_unit_file_with_content()
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('tests/Unit/EloquentPostRepositoryTest.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_feature_file_with_content()
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $file = $this->finder->get($this->module_path('tests/Feature/EloquentPostRepositoryTest.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_unit_namespace()
    {
        $this->app['config']->set('modules.paths.generator.test-unit.path', 'SuperTests/Unit');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('SuperTests/Unit/EloquentPostRepositoryTest.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_unit_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.test.namespace', 'SuperTests\\Unit');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('tests/Unit/EloquentPostRepositoryTest.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_feature_namespace()
    {
        $this->app['config']->set('modules.paths.generator.test-feature.path', 'SuperTests/Feature');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $file = $this->finder->get($this->module_path('SuperTests/Feature/EloquentPostRepositoryTest.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_feature_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.test-feature.namespace', 'SuperTests\\Feature');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $file = $this->finder->get($this->module_path('tests/Feature/EloquentPostRepositoryTest.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
