<?php

namespace Nwidart\Modules\Tests\Commands;

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
    /**
     * @var string
     */
    private $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    /** @test */
    public function it_generates_the_channel_class()
    {
        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Broadcasting/WelcomeChannel.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Broadcasting/WelcomeChannel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.channels.path', 'SuperChannel');

        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/SuperChannel/WelcomeChannel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.channels.namespace', 'SuperChannel');

        $code = $this->artisan('module:make-channel', ['name' => 'WelcomeChannel', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Broadcasting/WelcomeChannel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
