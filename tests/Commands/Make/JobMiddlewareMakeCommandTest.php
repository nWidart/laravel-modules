<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class JobMiddlewareMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_the_job_middleware_class()
    {
        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Jobs/Middleware/RateLimited.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/Middleware/RateLimited.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_job_middleware_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-job-middleware', ['name' => 'Foo/RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/Middleware/Foo/RateLimited.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.job-middleware.path', 'app/Queue/Middleware');

        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Queue/Middleware/RateLimited.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.job-middleware.namespace', 'Queue\\Middleware');

        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/Middleware/RateLimited.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_falls_back_to_the_jobs_path_without_a_job_middleware_config_key()
    {
        $this->withoutJobMiddlewareConfig();

        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/Middleware/RateLimited.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_the_fallback_follows_a_custom_jobs_path()
    {
        $this->withoutJobMiddlewareConfig();
        $this->app['config']->set('modules.paths.generator.jobs.path', 'app/Queue/Jobs');

        $code = $this->artisan('module:make-job-middleware', ['name' => 'Foo/RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Queue/Jobs/Middleware/Foo/RateLimited.php');

        $this->assertStringContainsString('namespace Modules\Blog\Queue\Jobs\Middleware\Foo;', $file);
        $this->assertSame(0, $code);
    }

    public function test_the_fallback_follows_a_jobs_namespace_override()
    {
        $this->withoutJobMiddlewareConfig();
        $this->app['config']->set('modules.paths.generator.jobs.namespace', 'Queue\\Jobs');

        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Jobs/Middleware/RateLimited.php');

        $this->assertStringContainsString('namespace Modules\Blog\Queue\Jobs\Middleware;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_keeps_an_existing_file_without_force()
    {
        $path = $this->modulePath.'/Jobs/Middleware/RateLimited.php';

        $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);

        $this->assertSame('edited', $this->finder->get($path));
        $this->assertSame(E_ERROR, $code);
    }

    public function test_it_overwrites_an_existing_file_with_force()
    {
        $path = $this->modulePath.'/Jobs/Middleware/RateLimited.php';

        $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-job-middleware', ['name' => 'RateLimited', 'module' => 'Blog', '--force' => true]);

        $this->assertStringContainsString('class RateLimited', $this->finder->get($path));
        $this->assertSame(0, $code);
    }

    /**
     * Simulate a modules config published before the job-middleware key existed.
     */
    private function withoutJobMiddlewareConfig(): void
    {
        $generators = $this->app['config']->get('modules.paths.generator');
        unset($generators['job-middleware']);

        $this->app['config']->set('modules.paths.generator', $generators);
    }
}
