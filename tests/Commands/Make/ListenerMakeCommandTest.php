<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
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

    public function test_it_generates_a_new_event_class()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
        );

        $this->assertTrue(is_file($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_sync_event_with_content()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_sync_event_in_a_subdirectory_with_content()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'User/WasCreated']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_sync_duck_event_with_content()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_queued_event_with_content()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated', '--queued' => true]
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_queued_event_in_a_subdirectory_with_content()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'User/WasCreated', '--queued' => true]
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_queued_duck_event_with_content()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--queued' => true]
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.listener.path', 'Events/Handlers');

        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
        );

        $file = $this->finder->get($this->getModuleBasePath().'/Events/Handlers/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.listener.namespace', 'Events\\Handlers');

        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
