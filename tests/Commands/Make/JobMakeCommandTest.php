<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class JobMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_the_job_class()
    {
        $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Jobs/SomeJob.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/SomeJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_sync_job_file_with_content()
    {
        $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog', '--sync' => true]);

        $file = $this->finder->get($this->modulePath.'/Jobs/SomeJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.jobs.path', 'SuperJobs');

        $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/SuperJobs/SomeJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.jobs.namespace', 'SuperJobs');

        $code = $this->artisan('module:make-job', ['name' => 'SomeJob', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/SomeJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
