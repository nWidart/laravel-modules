<?php

namespace Nwidart\Modules\Tests\Actuator;

use Illuminate\Console\Scheduling\Schedule;
use Nwidart\Actuator\ActuatorServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;

class ScheduledTasksTagTest extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelModulesServiceProvider::class,
            ActuatorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('actuator.exposure.include', ['scheduledtasks']);
    }

    public function test_a_task_of_a_module_has_the_module_tag()
    {
        $this->app->make(Schedule::class)->command('migrate')->daily();
        $this->app->register(new class($this->app) extends ModuleServiceProvider
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
                $schedule->call(fn () => null)->hourly();
            }
        });

        $response = $this->getJson('/actuator/scheduledtasks')->assertOk();

        $response->assertJsonMissingPath('cron.0.tags');
        $this->assertSame('Callback', $response->json('cron.1.runnable.target'));
        $this->assertSame(['module' => 'Blog'], $response->json('cron.1.tags'));
    }
}
