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
        $this->assertStringContainsString('Module [Blog] seeded.', $output);
        $this->assertStringContainsString('Module [Shop] seeded.', $output);
        $this->assertStringNotContainsString('FAIL', $output);
        $this->assertStringNotContainsString('ERROR', $output);
        $this->assertStringNotContainsString('WARN', $output);
    }

    public function test_it_prints_a_labelled_header_and_no_unlabelled_status_line_per_module()
    {
        $this->useSeeder('Blog', RecordingSeeder::class);
        $this->useSeeder('Shop', RecordingSeeder::class);

        $this->artisan('module:seed', ['module' => ['Blog', 'Shop']]);
        $output = $this->app[Kernel::class]->output();

        // Each module starts with its own header line, before the output of db:seed.
        $this->assertMatchesRegularExpression('/^  Seeding Blog Module \.+\s*$/m', $output);
        $this->assertMatchesRegularExpression('/^  Seeding Shop Module \.+\s*$/m', $output);
        $this->assertLessThan(strpos($output, 'Module [Blog] seeded.'), strpos($output, 'Seeding Blog Module'));
        $this->assertLessThan(strpos($output, 'Seeding Shop Module'), strpos($output, 'Module [Blog] seeded.'));
        // No line made only of dots, a time and a status.
        $this->assertDoesNotMatchRegularExpression('/^\s*\.+ [^\n]*(DONE|FAIL)\s*$/m', $output);
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
        $this->assertStringContainsString('Module [Blog] has no seeder: class [Modules\Blog\Database\Seeders\BlogDatabaseSeeder] not found.', $output);
        $this->assertStringNotContainsString('seeded.', $output);
        // The warning is printed after the module's header line.
        $this->assertMatchesRegularExpression('/Seeding Blog Module \.+\s*\n\s*WARN  Module \[Blog\] has no seeder/', $output);
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
        $this->assertStringContainsString('Module [Blog] seeded.', $output);
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
        $this->assertStringContainsString('Module [Blog] seeding failed.', $output);
        $this->assertStringNotContainsString('Module [Blog] seeded.', $output);
        $this->assertStringContainsString('Module [Shop] seeded.', $output);
        $this->assertDoesNotMatchRegularExpression('/^\s*\.+ [^\n]*(DONE|FAIL)\s*$/m', $output);
    }

    public function test_it_fails_when_db_seed_is_declined_in_production()
    {
        $this->useSeeder('Blog', RecordingSeeder::class);
        $this->app['env'] = 'production';

        $this->mockConsoleOutput = true;
        $this->artisan('module:seed', ['module' => ['Blog']])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->expectsOutputToContain('Module [Blog] seeding failed.')
            ->doesntExpectOutputToContain('Module [Blog] seeded.')
            ->assertFailed();

        $this->assertSame(0, RecordingSeeder::$runs);
    }

    public function test_it_does_not_report_the_module_as_seeded_when_db_seed_is_cancelled()
    {
        $this->useSeeder('Blog', RecordingSeeder::class);
        $this->app['env'] = 'production';

        $code = $this->artisan('module:seed', ['module' => ['Blog'], '--no-interaction' => true]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(1, $code);
        $this->assertSame(0, RecordingSeeder::$runs);
        $this->assertStringNotContainsString('Module [Blog] seeded.', $output);
        $this->assertDoesNotMatchRegularExpression('/^\s*\.+ [^\n]*(DONE|FAIL)\s*$/m', $output);
        $this->assertMatchesRegularExpression('/Seeding Blog Module \.+.*Command cancelled\..*Module \[Blog\] seeding failed\./s', $output);
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
