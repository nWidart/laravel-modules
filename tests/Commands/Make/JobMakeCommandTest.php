<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class JobMakeCommandTest extends BaseTestCase
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

    public function test_generated_queued_job_uses_the_foundation_queueable_trait()
    {
        $this->assertSame(0, $this->artisan('module:make-job', ['name' => 'LoadedQueuedJob', 'module' => 'Blog']));

        $class = $this->loadGeneratedJob('LoadedQueuedJob');

        $this->assertContains(ShouldQueue::class, class_implements($class));
        $this->assertSame([Queueable::class], array_values(class_uses($class)));
    }

    public function test_generated_sync_job_loads_and_is_not_queued()
    {
        $this->assertSame(0, $this->artisan('module:make-job', ['name' => 'LoadedSyncJob', 'module' => 'Blog', '--sync' => true]));

        $class = $this->loadGeneratedJob('LoadedSyncJob');

        $this->assertNotContains(ShouldQueue::class, class_implements($class));
        $this->assertSame([Dispatchable::class], array_values(class_uses($class)));
    }

    /**
     * Load a job class generated in the Blog module and return its name.
     */
    private function loadGeneratedJob(string $class): string
    {
        $fqcn = 'Modules\\Blog\\Jobs\\'.$class;

        if (! class_exists($fqcn, false)) {
            require $this->modulePath.'/Jobs/'.$class.'.php';
        }

        return $fqcn;
    }
}
