<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Facades\Event;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Events\ModuleDeleted;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ModuleDeleteCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var FileActivator
     */
    private $activator;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->activator = new FileActivator($this->app);
    }

    /** @test */
    public function it_can_delete_a_module_from_disk(): void
    {
        $this->artisan('module:make', ['name' => ['WrongModule']]);
        $this->assertDirectoryExists(base_path('modules/WrongModule'));

        $code = $this->artisan('module:delete', ['module' => 'WrongModule']);
        $this->assertFileDoesNotExist(base_path('modules/WrongModule'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_deletes_modules_from_status_file(): void
    {
        $this->artisan('module:make', ['name' => ['WrongModule']]);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $code = $this->artisan('module:delete', ['module' => 'WrongModule']);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function an_event_is_emitted_when_a_module_is_deleted()
    {
        Event::fake();

        $name = 'WrongModule';

        $this->artisan('module:make', ['name' => [$name]]);
        $this->artisan('module:delete', ['module' => $name]);

        Event::assertDispatched(ModuleDeleted::class, function ($event) use ($name) {
            return $event->name === $name;
        });
    }
}
