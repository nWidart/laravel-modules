<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RouteProviderMakeCommandTest extends BaseTestCase
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
    public function it_generates_a_new_service_provider_class()
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Providers/RouteServiceProvider.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

        $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/SuperProviders/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
    }
}
