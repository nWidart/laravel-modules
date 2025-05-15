<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class NotificationMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_the_notification_class()
    {
        $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Notifications/WelcomeNotification.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Notifications/WelcomeNotification.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.notifications.path', 'SuperNotifications');

        $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('SuperNotifications/WelcomeNotification.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.notifications.namespace', 'SuperNotifications');

        $code = $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('Notifications/WelcomeNotification.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
