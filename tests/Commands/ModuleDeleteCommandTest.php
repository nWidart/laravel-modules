<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Activators\FileActivator;
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

    /**
     * @var string
     */
    protected $confirmationMessage = 'Are you sure you want to delete module WrongModule? (Y/n)';

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->activator = new FileActivator($this->app);
    }

    public function withoutMockingConsoleOutput()
    {
        //
    }

    /** @test */
    public function it_deletes_modules_when_user_has_confirmed(): void
    {
        $this->artisan('module:make', ['name' => ['WrongModule']]);
        $this->assertDirectoryExists(base_path('modules/WrongModule'));

        $this->artisan('module:delete', ['module' => 'WrongModule'])
            ->expectsConfirmation($this->confirmationMessage, 'no')
            ->assertExitCode(1);
        $this->assertDirectoryExists(base_path('modules/WrongModule'));

        $this->artisan('module:delete', ['module' => 'WrongModule'])
            ->expectsConfirmation($this->confirmationMessage, 'yes')
            ->assertExitCode(0);
        $this->assertDirectoryNotExists(base_path('modules/WrongModule'));
    }

    /** @test */
    public function it_can_delete_a_module_from_disk(): void
    {
        $this->artisan('module:make', ['name' => ['WrongModule']]);
        $this->assertDirectoryExists(base_path('modules/WrongModule'));

        $this->artisan('module:delete', ['module' => 'WrongModule'])
            ->expectsConfirmation($this->confirmationMessage, 'yes')
            ->assertExitCode(0);
        $this->assertDirectoryNotExists(base_path('modules/WrongModule'));
    }

    /** @test */
    public function it_deletes_modules_from_status_file(): void
    {
        $this->artisan('module:make', ['name' => ['WrongModule']]);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->artisan('module:delete', ['module' => 'WrongModule'])
            ->expectsConfirmation($this->confirmationMessage, 'yes')
            ->assertExitCode(0);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }
}
