<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ListenerMakeCommandTest extends BaseTestCase
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
    public function it_generates_a_new_event_class()
    {
        $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
        );

        $this->assertTrue(is_file($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php'));
    }

    /** @test */
    public function it_generated_correct_sync_event_with_content()
    {
        $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
        );

        $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generated_correct_sync_duck_event_with_content()
    {
        $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
        );

        $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generated_correct_queued_event_with_content()
    {
        $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated', '--queued' => true]
        );

        $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generated_correct_queued_duck_event_with_content()
    {
        $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--queued' => true]
        );

        $file = $this->finder->get($this->modulePath . '/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.listener.path', 'Events/Handlers');

        $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
        );

        $file = $this->finder->get($this->modulePath . '/Events/Handlers/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
    }
}
