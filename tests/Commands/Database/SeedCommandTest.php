<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\FailingSeeder;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\RecordingSeeder;

class SeedCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->repository = $this->app[RepositoryInterface::class];

        RecordingSeeder::$runs = 0;
        RecordingSeeder::$connections = [];
    }

    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';

        $this->repository->delete('Blog');
        $this->repository->delete('Shop');
        parent::tearDown();
    }

    public function test_it_succeeds_when_every_module_is_seeded()
    {
        $this->useSeeder('Blog', RecordingSeeder::class);
        $this->useSeeder('Shop', RecordingSeeder::class);

        $code = $this->artisan('module:seed', ['module' => ['Blog', 'Shop']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertSame(2, RecordingSeeder::$runs);
        $this->assertStringContainsString('DONE', $output);
        $this->assertStringNotContainsString('FAIL', $output);
    }

    public function test_it_fails_when_a_module_seeder_throws_and_still_seeds_the_other_modules()
    {
        $this->useSeeder('Blog', FailingSeeder::class);
        $this->useSeeder('Shop', RecordingSeeder::class);

        $code = $this->artisan('module:seed', ['module' => ['Blog', 'Shop']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(1, $code);
        $this->assertSame(1, RecordingSeeder::$runs);
        $this->assertStringContainsString('Seeding failed on purpose.', $output);
        $this->assertStringContainsString('FAIL', $output);
    }

    public function test_it_fails_when_db_seed_is_declined_in_production()
    {
        $this->useSeeder('Blog', RecordingSeeder::class);
        $this->app['env'] = 'production';

        $this->mockConsoleOutput = true;
        $this->artisan('module:seed', ['module' => ['Blog']])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed();

        $this->assertSame(0, RecordingSeeder::$runs);
    }

    public function test_it_fails_without_a_module_when_not_interactive()
    {
        $code = $this->artisan('module:seed', ['--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('No module given. Pass a module name or --all.', $this->app[Kernel::class]->output());
    }

    private function useSeeder(string $module, string $seeder): void
    {
        $this->repository->find($module)->json()->set('migration', ['seeds' => [$seeder]])->save();
    }
}
