<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;

class ListenerMakeCommandTest extends BaseTestCase
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

    public function test_it_uses_the_configured_event_path_for_the_event_import()
    {
        $this->app['config']->set('modules.paths.generator.event.path', 'app/Domain/Events');

        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'User/WasCreated']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertStringContainsString('use Modules\\Blog\\Domain\\Events\\User\\WasCreated;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_configured_event_namespace_for_the_event_import()
    {
        $this->app['config']->set('modules.paths.generator.event.namespace', 'Domain\\Events');

        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertStringContainsString('use Modules\\Blog\\Domain\\Events\\UserWasCreated;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_configured_module_namespace_for_the_event_import()
    {
        $this->app['config']->set('modules.namespace', 'Domains');

        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertStringContainsString('use Domains\\Blog\\Events\\UserWasCreated;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_sync_event_with_a_fully_qualified_app_event()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'App\\Events\\UserWasCreated']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_queued_event_with_a_fully_qualified_app_event()
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'App\\Events\\UserWasCreated', '--queued' => true]
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    #[DataProvider('qualifiedEventProvider')]
    public function test_it_keeps_a_fully_qualified_event_as_given(string $event, bool $queued, string $use, string $short)
    {
        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => $event, '--queued' => $queued]
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertStringContainsString("use {$use};", $file);
        $this->assertStringContainsString("public function handle({$short} \$event): void", $file);
        $this->assertStringNotContainsString('Modules\\Blog\\Events', $file);
        $this->assertSame(0, $code);
    }

    public static function qualifiedEventProvider(): array
    {
        $cases = [
            'app event' => ['App\\Events\\Bar', 'App\\Events\\Bar', 'Bar'],
            'leading backslash' => ['\\App\\Events\\Bar', 'App\\Events\\Bar', 'Bar'],
            'leading backslash outside app' => ['\\Vendor\\Package\\Events\\Bar', 'Vendor\\Package\\Events\\Bar', 'Bar'],
            'slashes' => ['App/Events/Bar', 'App\\Events\\Bar', 'Bar'],
            'illuminate event' => ['Illuminate\\Auth\\Events\\Login', 'Illuminate\\Auth\\Events\\Login', 'Login'],
            'other module event' => ['Modules\\Other\\Events\\X', 'Modules\\Other\\Events\\X', 'X'],
        ];

        $data = [];

        foreach ($cases as $name => [$event, $use, $short]) {
            $data[$name.' (sync)'] = [$event, false, $use, $short];
            $data[$name.' (queued)'] = [$event, true, $use, $short];
        }

        return $data;
    }

    public function test_it_keeps_an_event_in_the_configured_module_namespace_as_given()
    {
        $this->app['config']->set('modules.namespace', 'Domains');

        $code = $this->artisan(
            'module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'Domains\\Other\\Events\\X']
        );

        $file = $this->finder->get($this->modulePath.'/Listeners/NotifyUsersOfANewPost.php');

        $this->assertStringContainsString('use Domains\\Other\\Events\\X;', $file);
        $this->assertStringContainsString('public function handle(X $event): void', $file);
        $this->assertSame(0, $code);
    }
}
