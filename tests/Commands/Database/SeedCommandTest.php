<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Commands\Database\SeedCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\FailingSeeder;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\RecordingSeeder;
use RuntimeException;

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

    public function test_get_module_by_name_only_uses_methods_of_the_repository_interface()
    {
        // A mock of the interface has no has() method, like a custom repository implementation.
        $module = $this->repository->find('Blog');
        $repository = $this->createStub(RepositoryInterface::class);
        $repository->method('find')->willReturnMap([['Blog', $module], ['Unknown', null]]);
        $this->app->instance('modules', $repository);

        $command = $this->app->make(SeedCommand::class);
        $command->setLaravel($this->app);

        $this->assertSame($module, $command->getModuleByName('Blog'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module [Unknown] does not exists.');

        $command->getModuleByName('Unknown');
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
        $this->assertStringNotContainsString('WARN', $output);
    }

    public function test_it_seeds_the_conventional_database_seeder_without_a_warning()
    {
        if (! class_exists('Modules\Shop\Database\Seeders\ShopDatabaseSeeder')) {
            class_alias(RecordingSeeder::class, 'Modules\Shop\Database\Seeders\ShopDatabaseSeeder');
        }

        $code = $this->artisan('module:seed', ['module' => ['Shop']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertSame(1, RecordingSeeder::$runs);
        $this->assertStringContainsString('DONE', $output);
        $this->assertStringNotContainsString('WARN', $output);
    }

    public function test_it_warns_when_a_module_has_no_database_seeder()
    {
        $code = $this->artisan('module:seed', ['module' => ['Blog']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertSame(0, RecordingSeeder::$runs);
        $this->assertStringContainsString('DONE', $output);
        $this->assertStringContainsString('Module [Blog] has no seeder: class [Modules\Blog\Database\Seeders\BlogDatabaseSeeder] not found.', $output);
        // The warning is printed after the task line, so the line still ends with its status.
        $this->assertMatchesRegularExpression('/Seeding Blog Module \.+ [^\n]*DONE\n/', $output);
    }

    public function test_it_warns_for_each_listed_seeder_that_does_not_exist_and_runs_the_others()
    {
        $this->repository->find('Blog')->json()->set('migration', ['seeds' => [
            'Modules\Blog\Database\Seeders\MissingSeeder',
            RecordingSeeder::class,
            'Modules\Blog\Database\Seeders\OtherMissingSeeder',
        ]])->save();

        $code = $this->artisan('module:seed', ['module' => ['Blog']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertSame(1, RecordingSeeder::$runs);
        $this->assertStringContainsString('DONE', $output);
        $this->assertStringContainsString('Module [Blog] seeder [Modules\Blog\Database\Seeders\MissingSeeder] not found, skipped.', $output);
        $this->assertStringContainsString('Module [Blog] seeder [Modules\Blog\Database\Seeders\OtherMissingSeeder] not found, skipped.', $output);
        $this->assertStringNotContainsString('has no seeder', $output);
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
