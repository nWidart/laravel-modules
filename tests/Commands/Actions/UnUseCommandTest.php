<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Nwidart\Modules\Constants\ModuleEvent;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class UnUseCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        $this->repository->forgetUsed();
        $this->repository->delete('Blog');
        $this->repository->delete('Shop');
        parent::tearDown();
    }

    public function test_it_forgets_the_used_module_without_a_module_name_or_a_prompt()
    {
        $this->repository->setUsed('Blog');
        Event::fake();

        $code = $this->artisan('module:unuse', ['--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertFileDoesNotExist($this->usedFile());
        $this->assertSame(1, substr_count(Artisan::output(), 'Forget Using Blog Module'));
        $this->assertUnusedFiredFor('Blog');
    }

    public function test_it_still_accepts_a_module_name()
    {
        $this->repository->setUsed('Blog');
        Event::fake();

        $code = $this->artisan('module:unuse', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertFileDoesNotExist($this->usedFile());
        $this->assertUnusedFiredFor('Blog');
    }

    public function test_it_ignores_an_unknown_module_name()
    {
        $this->repository->setUsed('Blog');

        $code = $this->artisan('module:unuse', ['module' => ['Unknown']]);

        $this->assertSame(0, $code);
        $this->assertFileDoesNotExist($this->usedFile());
    }

    public function test_it_still_accepts_the_all_option_and_forgets_the_used_module_once()
    {
        $this->repository->setUsed('Blog');
        Event::fake();

        $code = $this->artisan('module:unuse', ['--all' => true]);

        $output = Artisan::output();
        $this->assertSame(0, $code);
        $this->assertFileDoesNotExist($this->usedFile());
        $this->assertSame(1, substr_count($output, 'Forget Using'));
        $this->assertStringContainsString('Forget Using Blog Module', $output);
        $this->assertUnusedFiredFor('Blog');
    }

    public function test_it_succeeds_without_an_event_when_no_module_is_used()
    {
        Event::fake();

        $code = $this->artisan('module:unuse', ['--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No module is in use.', Artisan::output());
        Event::assertNotDispatched('modules.blog.'.ModuleEvent::UNUSED);
        Event::assertNotDispatched('modules.shop.'.ModuleEvent::UNUSED);
    }

    public function test_it_deletes_a_used_file_that_names_a_missing_module()
    {
        $this->app['files']->put($this->repository->getUsedStoragePath(), 'Ghost');
        Event::fake();

        $code = $this->artisan('module:unuse', ['--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No module is in use.', Artisan::output());
        $this->assertFileDoesNotExist($this->usedFile());
        Event::assertNotDispatched('modules.ghost.'.ModuleEvent::UNUSED);
    }

    private function usedFile(): string
    {
        return storage_path('app/modules/modules.used');
    }

    private function assertUnusedFiredFor(string $name): void
    {
        Event::assertDispatched(
            'modules.'.strtolower($name).'.'.ModuleEvent::UNUSED,
            fn (string $event, array $payload) => $payload[0] instanceof Module && $payload[0]->getName() === $name
        );
        Event::assertDispatchedTimes('modules.'.strtolower($name).'.'.ModuleEvent::UNUSED, 1);
    }
}
