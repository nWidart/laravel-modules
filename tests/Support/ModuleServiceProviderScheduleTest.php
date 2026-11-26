<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Actuator\ScheduledTaskModules;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;

class ModuleServiceProviderScheduleTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Start from an unresolved Schedule singleton.
        $this->app->forgetInstance(Schedule::class);
    }

    protected function tearDown(): void
    {
        Schedule::alwaysOnOneServer(false);
        File::deleteDirectory(base_path('modules/Blog'));

        parent::tearDown();
    }

    public function test_it_does_not_resolve_the_schedule_when_configure_schedules_is_not_overridden()
    {
        $this->app->register($this->providerWithoutSchedules());

        $this->assertFalse($this->app->resolved(Schedule::class));
        $this->assertSame(0, $this->countInspireEvents($this->app->make(Schedule::class)));
    }

    public function test_it_does_not_resolve_the_schedule_when_a_module_with_schedules_boots()
    {
        $this->app->register($this->providerWithSchedules());

        $this->assertFalse($this->app->resolved(Schedule::class));
    }

    public function test_it_registers_the_module_events_once_when_the_schedule_is_resolved_after_boot()
    {
        $this->app->register($this->providerWithSchedules());

        $this->assertSame(1, $this->countInspireEvents($this->app->make(Schedule::class)));
        $this->assertSame(1, $this->countInspireEvents($this->app->make(Schedule::class)));
    }

    public function test_it_registers_the_module_events_once_when_the_schedule_was_resolved_before_boot()
    {
        $schedule = $this->app->make(Schedule::class);

        $this->app->register($this->providerWithSchedules());

        $this->assertSame(1, $this->countInspireEvents($schedule));
        $this->assertSame(1, $this->countInspireEvents($this->app->make(Schedule::class)));
    }

    public function test_it_registers_the_module_events_once_on_a_rebuilt_schedule()
    {
        $this->app->register($this->providerWithSchedules());
        $this->app->make(Schedule::class);

        $this->app->forgetInstance(Schedule::class);

        $this->assertSame(1, $this->countInspireEvents($this->app->make(Schedule::class)));
    }

    public function test_it_claims_only_the_module_events_for_the_module()
    {
        $schedule = $this->app->make(Schedule::class);
        $app = $schedule->command('migrate')->daily();

        $this->app->register($this->providerWithSchedules());

        $modules = $this->app->make(ScheduledTaskModules::class);
        $events = collect($schedule->events());
        $this->assertSame('Blog', $modules->moduleOf($events->first(fn (Event $event) => str_contains((string) $event->command, 'inspire'))));
        $this->assertNull($modules->moduleOf($app));
        $this->assertSame([], $modules->tags($app));
    }

    public function test_it_claims_the_events_under_the_module_json_name()
    {
        File::ensureDirectoryExists(base_path('modules/Blog'));
        File::put(base_path('modules/Blog/module.json'), json_encode(['name' => 'blog', 'providers' => []]));
        $this->app[RepositoryInterface::class]->resetModules();
        $schedule = $this->app->make(Schedule::class);

        $this->app->register($this->providerWithSchedules());

        $this->assertSame('blog', $this->app->make(ScheduledTaskModules::class)->moduleOf($this->inspireEvent($schedule)));
    }

    public function test_a_module_registered_inside_another_module_schedules_keeps_its_own_events()
    {
        $schedule = $this->app->make(Schedule::class);
        $inner = $this->providerWithSchedules();

        $this->app->register(new class($this->app, $inner) extends ModuleServiceProvider
        {
            protected string $name = 'Shop';

            protected string $nameLower = 'shop';

            public function __construct($app, private ModuleServiceProvider $inner)
            {
                parent::__construct($app);
            }

            public function register(): void {}

            public function boot(): void
            {
                $this->registerCommandSchedules();
            }

            protected function configureSchedules(Schedule $schedule): void
            {
                $schedule->command('migrate')->daily();
                $this->app->register($this->inner);
            }
        });

        $modules = $this->app->make(ScheduledTaskModules::class);
        $migrate = collect($schedule->events())->first(fn (Event $event) => str_contains((string) $event->command, 'migrate'));
        $this->assertSame('Blog', $modules->moduleOf($this->inspireEvent($schedule)));
        $this->assertSame('Shop', $modules->moduleOf($migrate));
    }

    public function test_it_does_not_put_existing_events_on_one_server_when_always_on_one_server_is_on()
    {
        Schedule::alwaysOnOneServer();
        $schedule = $this->app->make(Schedule::class);
        $existing = $schedule->command('migrate')->daily();

        $this->app->register($this->providerWithSchedules());

        // The app turns the flag off before schedule:run reads the events.
        Schedule::alwaysOnOneServer(false);
        $this->assertFalse($existing->onOneServer);
        $this->assertFalse($this->inspireEvent($schedule)->onOneServer);
    }

    public function test_schedule_list_shows_the_module_events_once()
    {
        $this->app->register($this->providerWithSchedules());

        Artisan::call('schedule:list');

        $this->assertSame(1, substr_count(Artisan::output(), 'inspire'));
    }

    private function providerWithoutSchedules(): ModuleServiceProvider
    {
        return new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function register(): void {}

            public function boot(): void
            {
                $this->registerCommandSchedules();
            }
        };
    }

    private function providerWithSchedules(): ModuleServiceProvider
    {
        return new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function register(): void {}

            public function boot(): void
            {
                $this->registerCommandSchedules();
            }

            protected function configureSchedules(Schedule $schedule): void
            {
                $schedule->command('inspire')->hourly();
            }
        };
    }

    private function inspireEvent(Schedule $schedule): Event
    {
        return collect($schedule->events())->first(fn (Event $event) => str_contains((string) $event->command, 'inspire'));
    }

    private function countInspireEvents(Schedule $schedule): int
    {
        return collect($schedule->events())
            ->filter(fn (Event $event) => str_contains((string) $event->command, 'inspire'))
            ->count();
    }
}
