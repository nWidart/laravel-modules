<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
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

    private function countInspireEvents(Schedule $schedule): int
    {
        return collect($schedule->events())
            ->filter(fn (Event $event) => str_contains((string) $event->command, 'inspire'))
            ->count();
    }
}
