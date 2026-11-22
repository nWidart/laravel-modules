<?php

namespace Nwidart\Modules\Tests\Actuator;

use Nwidart\Actuator\Health\Status;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Actuator\ModulesHealthIndicator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class ModulesHealthIndicatorTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->artisan('module:disable', ['module' => ['Shop']]);
    }

    protected function tearDown(): void
    {
        $repository = $this->app[RepositoryInterface::class];
        $repository->delete('Blog');
        $repository->delete('Shop');
        $this->app[ActivatorInterface::class]->reset();

        parent::tearDown();
    }

    public function test_it_is_up_and_lists_enabled_and_disabled_modules()
    {
        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => true],
                'Shop' => ['enabled' => false],
            ],
        ], $health->details);
    }

    public function test_it_is_down_when_an_enabled_module_is_not_on_disk()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Ghost', true);

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Down, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => true],
                'Shop' => ['enabled' => false],
                'Ghost' => ['enabled' => true, 'error' => 'Enabled in the status store, not found on disk.'],
            ],
        ], $health->details);
    }

    public function test_a_disabled_module_that_is_not_on_disk_does_not_change_the_status()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Ghost', false);

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertArrayNotHasKey('Ghost', $health->details['modules']);
    }

    public function test_it_matches_stored_names_case_insensitively_like_the_repository()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('blog', true);

        $health = $this->app->make(ModulesHealthIndicator::class)->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame(['Blog', 'Shop'], array_keys($health->details['modules']));
    }

    public function test_it_skips_the_missing_module_check_with_an_activator_that_is_not_a_file_activator()
    {
        $this->app[ActivatorInterface::class]->setActiveByName('Ghost', true);

        $activator = new class implements ActivatorInterface
        {
            public function enable(Module $module): void {}

            public function disable(Module $module): void {}

            public function hasStatus(Module|string $module, bool $status): bool
            {
                $name = $module instanceof Module ? $module->getName() : $module;

                return ($name === 'Shop') === $status;
            }

            public function setActive(Module $module, bool $active): void {}

            public function setActiveByName(string $name, bool $active): void {}

            public function delete(Module $module): void {}

            public function reset(): void {}
        };

        $health = (new ModulesHealthIndicator($this->app[RepositoryInterface::class], $activator))->health();

        $this->assertSame(Status::Up, $health->status);
        $this->assertSame([
            'modules' => [
                'Blog' => ['enabled' => false],
                'Shop' => ['enabled' => true],
            ],
        ], $health->details);
    }

    public function test_file_activator_returns_the_stored_statuses()
    {
        $activator = $this->app[ActivatorInterface::class];
        $this->assertInstanceOf(FileActivator::class, $activator);

        $activator->setActiveByName('Ghost', true);

        $this->assertSame(['Blog' => true, 'Shop' => false, 'Ghost' => true], $activator->getStatuses());
    }
}
