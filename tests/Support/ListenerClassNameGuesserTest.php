<?php

namespace Nwidart\Modules\Tests\Support;

use Composer\Autoload\ClassLoader;
use Illuminate\Auth\Events\Registered;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;
use Nwidart\Modules\Support\ListenerClassNameGuesser;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Tests\BaseTestCase;
use SplFileInfo;

class ListenerClassNameGuesserTest extends BaseTestCase
{
    private Filesystem $finder;

    private ClassLoader $loader;

    /**
     * Files and folders written by the tests, removed in tearDown.
     *
     * @var array<int, string>
     */
    private array $created = [];

    /**
     * Laravel's static event discovery paths before the test, restored in tearDown.
     *
     * @var iterable<int, string>|null
     */
    private ?iterable $eventDiscoveryPaths = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->app['files'];

        // The fixture classes live in the testbench skeleton, which composer does not autoload.
        $this->loader = new ClassLoader;
        $this->loader->addPsr4('App\\', app_path());
        $this->loader->addPsr4('Modules\\Points\\', base_path('modules/Points/app'));
        $this->loader->addPsr4('Modules\\Legacy\\', base_path('modules/Legacy'));
        $this->loader->register();

        $this->eventDiscoveryPaths = (fn () => static::$eventDiscoveryPaths)->bindTo(null, EventServiceProvider::class)();
    }

    protected function tearDown(): void
    {
        $this->loader->unregister();

        foreach ($this->created as $path) {
            $this->finder->isDirectory($path) ? $this->finder->deleteDirectory($path) : $this->finder->delete($path);
        }

        DiscoverEvents::$guessClassNamesUsingCallback = null;
        $this->restoreEventDiscoveryPaths();

        parent::tearDown();
    }

    public function test_the_service_provider_registers_the_guesser()
    {
        $this->assertInstanceOf(ListenerClassNameGuesser::class, DiscoverEvents::$guessClassNamesUsingCallback);
    }

    public function test_it_discovers_a_module_listener_handling_an_app_event()
    {
        $this->writeAppEvent();
        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Modules\Points\Listeners', 'SyncPoints', '\App\Events\PointsEarned');

        $events = $this->discover();

        $this->assertSame(['Modules\Points\Listeners\SyncPoints@handle'], $events['App\Events\PointsEarned']);
    }

    public function test_it_discovers_a_module_listener_handling_a_module_event()
    {
        $this->writeClass('modules/Points/app/Events/PointsRedeemed.php', 'Modules\Points\Events', 'PointsRedeemed');
        $this->writeClass('modules/Points/app/Listeners/Rewards/ShipReward.php', 'Modules\Points\Listeners\Rewards', 'ShipReward', '\Modules\Points\Events\PointsRedeemed');

        $events = $this->discover();

        $this->assertSame(['Modules\Points\Listeners\Rewards\ShipReward@handle'], $events['Modules\Points\Events\PointsRedeemed']);
    }

    public function test_it_keeps_laravel_class_names_for_app_listeners()
    {
        $this->writeAppEvent();
        $this->writeClass('app/Listeners/AwardBadge.php', 'App\Listeners', 'AwardBadge', '\App\Events\PointsEarned');
        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Modules\Points\Listeners', 'SyncPoints', '\App\Events\PointsEarned');

        $events = $this->discover();

        $this->assertEqualsCanonicalizing([
            'App\Listeners\AwardBadge@handle',
            'Modules\Points\Listeners\SyncPoints@handle',
        ], $events['App\Events\PointsEarned']);
        $this->assertSame('App\Listeners\AwardBadge', $this->guess(app_path('Listeners/AwardBadge.php')));
    }

    public function test_it_maps_the_module_root_when_the_app_folder_is_empty()
    {
        config(['modules.paths.app_folder' => '']);

        $this->writeClass('modules/Legacy/Events/OrderShipped.php', 'Modules\Legacy\Events', 'OrderShipped');
        $this->writeClass('modules/Legacy/Listeners/SendInvoice.php', 'Modules\Legacy\Listeners', 'SendInvoice', '\Modules\Legacy\Events\OrderShipped');

        $events = DiscoverEvents::within([base_path('modules/Legacy/Listeners')], base_path());

        $this->assertSame(['Modules\Legacy\Listeners\SendInvoice@handle'], $events['Modules\Legacy\Events\OrderShipped']);
    }

    public function test_it_uses_the_configured_modules_namespace()
    {
        config(['modules.namespace' => 'Domain\\']);

        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Domain\Points\Listeners', 'SyncPoints');

        $this->assertSame('Domain\Points\Listeners\SyncPoints', $this->guess(base_path('modules/Points/app/Listeners/SyncPoints.php')));
    }

    public function test_it_strips_only_a_whole_app_folder_segment()
    {
        $this->writeClass('modules/Points/application/Listeners/Audit.php', 'Modules\Points\application\Listeners', 'Audit');

        $this->assertSame('Modules\Points\application\Listeners\Audit', $this->guess(base_path('modules/Points/application/Listeners/Audit.php')));
    }

    public function test_it_delegates_non_module_files_to_a_callback_set_before_it()
    {
        $calls = [];
        DiscoverEvents::guessClassNamesUsing(function (SplFileInfo $file, string $basePath) use (&$calls) {
            $calls[] = $file->getFilename();

            return 'Custom\\'.$file->getBasename('.php');
        });
        ListenerClassNameGuesser::register();

        $this->writeClass('app/Listeners/AwardBadge.php', 'App\Listeners', 'AwardBadge');
        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Modules\Points\Listeners', 'SyncPoints');

        $this->assertSame('Custom\AwardBadge', $this->guess(app_path('Listeners/AwardBadge.php')));
        $this->assertSame('Modules\Points\Listeners\SyncPoints', $this->guess(base_path('modules/Points/app/Listeners/SyncPoints.php')));
        $this->assertSame(['AwardBadge.php'], $calls);
    }

    public function test_the_application_event_provider_does_not_discover_module_listeners_by_default()
    {
        $this->writeAppEvent();
        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Modules\Points\Listeners', 'SyncPoints', '\App\Events\PointsEarned');

        $events = (new EventServiceProvider($this->app))->getEvents();

        $this->assertArrayNotHasKey('App\Events\PointsEarned', $events);
    }

    public function test_the_application_event_provider_discovers_module_listeners_in_its_discovery_paths()
    {
        $this->writeAppEvent();
        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Modules\Points\Listeners', 'SyncPoints', '\App\Events\PointsEarned');

        // What `withEvents(discover: [...])` in bootstrap/app.php does.
        EventServiceProvider::setEventDiscoveryPaths([app_path('Listeners'), base_path('modules/*/app/Listeners')]);
        $this->app->register(new EventServiceProvider($this->app), true);

        $this->assertContains('Modules\Points\Listeners\SyncPoints@handle', Event::getRawListeners()['App\Events\PointsEarned'] ?? []);
    }

    public function test_a_generated_module_event_provider_does_not_discover_listeners()
    {
        $this->writeAppEvent();
        $this->writeClass('modules/Points/app/Listeners/SyncPoints.php', 'Modules\Points\Listeners', 'SyncPoints', '\App\Events\PointsEarned');
        $this->track('modules/Points/app/Providers/EventServiceProvider.php');
        $this->finder->ensureDirectoryExists(base_path('modules/Points/app/Providers'));
        $this->finder->put(base_path('modules/Points/app/Providers/EventServiceProvider.php'), (new Stub('/event-provider.stub', [
            'NAMESPACE' => 'Modules\Points\Providers',
            'CLASS' => 'EventServiceProvider',
        ]))->render());

        EventServiceProvider::setEventDiscoveryPaths([base_path('modules/*/app/Listeners')]);
        $registered = count(Event::getRawListeners()[Registered::class] ?? []);
        $provider = new \Modules\Points\Providers\EventServiceProvider($this->app);
        $this->app->register($provider);

        // Laravel discovers only in its own EventServiceProvider, never in a subclass.
        $this->assertFalse($provider->shouldDiscoverEvents());
        $this->assertSame([], $provider->getEvents());
        $this->assertArrayNotHasKey('App\Events\PointsEarned', Event::getRawListeners());
        // The empty configureEmailVerification() keeps each module from adding the verification email listener again.
        $this->assertCount($registered, Event::getRawListeners()[Registered::class] ?? []);
    }

    public function test_registering_again_replaces_the_guesser_instead_of_chaining_it()
    {
        $first = DiscoverEvents::$guessClassNamesUsingCallback;

        ListenerClassNameGuesser::register();

        $this->writeClass('app/Listeners/AwardBadge.php', 'App\Listeners', 'AwardBadge');

        $this->assertNotSame($first, DiscoverEvents::$guessClassNamesUsingCallback);
        $this->assertSame('App\Listeners\AwardBadge', $this->guess(app_path('Listeners/AwardBadge.php')));
    }

    /**
     * Discover like `withEvents(discover: [app/Listeners, Modules/<star>/app/Listeners])` does.
     */
    private function discover(): array
    {
        $directories = array_filter(
            [app_path('Listeners'), ...glob(base_path('modules/*/app/Listeners'), GLOB_ONLYDIR)],
            'is_dir'
        );

        return DiscoverEvents::within(array_values($directories), base_path());
    }

    private function restoreEventDiscoveryPaths(): void
    {
        $paths = $this->eventDiscoveryPaths;

        (fn () => static::$eventDiscoveryPaths = $paths)->bindTo(null, EventServiceProvider::class)();
    }

    private function guess(string $path): string
    {
        return call_user_func(DiscoverEvents::$guessClassNamesUsingCallback, new SplFileInfo($path), base_path());
    }

    private function writeAppEvent(): void
    {
        $this->writeClass('app/Events/PointsEarned.php', 'App\Events', 'PointsEarned');
    }

    /**
     * Write a class to a path relative to the base path. With an event, the class is a listener for it.
     */
    private function writeClass(string $path, string $namespace, string $class, ?string $event = null): void
    {
        $body = $event === null ? '' : "public function handle({$event} \$event): void {}";

        $this->track($path);
        $this->finder->ensureDirectoryExists(dirname(base_path($path)));
        $this->finder->put(base_path($path), "<?php\n\nnamespace {$namespace};\n\nclass {$class}\n{\n    {$body}\n}\n");
    }

    /**
     * Remember the top-level folder (modules/<Name>) or the file (app/...) to delete in tearDown.
     */
    private function track(string $path): void
    {
        $segments = explode('/', $path);
        $target = $segments[0] === 'modules' ? base_path("modules/{$segments[1]}") : base_path($path);

        if (! in_array($target, $this->created, true)) {
            $this->created[] = $target;
        }

        foreach (['Listeners', 'Events'] as $folder) {
            if ($segments[0] === 'app' && $segments[1] === $folder && ! $this->finder->isDirectory(app_path($folder))) {
                $this->created[] = app_path($folder);
            }
        }
    }
}
