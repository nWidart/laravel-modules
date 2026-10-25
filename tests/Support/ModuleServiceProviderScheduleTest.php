<?php

namespace Nwidart\Modules\Tests\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
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
        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function registerSchedules(): void
            {
                $this->registerCommandSchedules();
            }
        };

        $provider->registerSchedules();

        $this->assertFalse($this->app->resolved(Schedule::class));
    }

    public function test_it_registers_the_schedules_of_a_module_that_overrides_configure_schedules()
    {
        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function registerSchedules(): void
            {
                $this->registerCommandSchedules();
            }

            protected function configureSchedules(Schedule $schedule): void
            {
                $schedule->command('inspire')->hourly();
            }
        };

        $provider->registerSchedules();

        $commands = collect($this->app->make(Schedule::class)->events())
            ->map(fn (Event $event) => $event->command);

        $this->assertTrue($commands->contains(fn (?string $command) => str_contains((string) $command, 'inspire')));
    }
}
