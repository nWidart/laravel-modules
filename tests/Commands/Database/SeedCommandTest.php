<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Mockery;
use Nwidart\Modules\Commands\Database\SeedCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SeedCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_reports_done_status_when_seeding_succeeds()
    {
        $this->artisan('module:seed', ['module' => ['Blog']]);

        $output = Artisan::output();

        $this->assertTrue(Str::contains($output, 'DONE'));
        $this->assertFalse(Str::contains($output, 'FAIL'));
    }

    public function test_it_reports_fail_status_when_seeding_throws_an_exception()
    {
        /** @var SeedCommand $command */
        $command = Mockery::mock(SeedCommand::class.'[moduleSeed]');
        $command->shouldReceive('moduleSeed')
            ->once()
            ->andThrow(new \RuntimeException('Seeding failed on purpose.'));
        $command->setLaravel($this->app);

        $tester = new CommandTester($command);
        $tester->execute(['module' => ['Blog']]);

        $output = $tester->getDisplay();

        $this->assertTrue(Str::contains($output, 'FAIL'));
        $this->assertFalse(Str::contains($output, 'DONE'));
    }
}
