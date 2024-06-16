<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RequestMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_form_request_class()
    {
        $code = $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Http/Requests/CreateBlogPostRequest.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Http/Requests/CreateBlogPostRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.request.path', 'SuperRequests');

        $code = $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/SuperRequests/CreateBlogPostRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.request.namespace', 'SuperRequests');

        $code = $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Http/Requests/CreateBlogPostRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
