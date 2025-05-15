<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ChannelMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_the_channel_class()
    {
        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Broadcasting/WelcomeChannel.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Broadcasting/WelcomeChannel.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_changes_default_path()
    {
        $this->app['config']->set('modules.paths.generator.channels.path', 'SuperChannel');

        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('SuperChannel/WelcomeChannel.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_changes_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.channels.namespace', 'SuperChannel');

        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Broadcasting/WelcomeChannel.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
