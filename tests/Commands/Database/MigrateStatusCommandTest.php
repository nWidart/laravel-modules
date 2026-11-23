<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Traits\MemoryConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;

class MigrateStatusCommandTest extends BaseTestCase
{
    private const MIGRATION = '2020_01_01_000000_create_blog_posts_table';

    private const EXTRA_MIGRATION = '2020_01_02_000000_create_feature_flags_table';

    private const APP_MIGRATION = '2020_01_03_000000_create_app_widgets_table';

    private const EARLY_MIGRATION = '2019_12_31_000000_create_blog_tags_table';

    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];

        $this->writeMigration(base_path('modules/Blog/database/migrations'), self::MIGRATION, 'blog_posts');
        $this->writeMigration(database_path('migrations'), self::APP_MIGRATION, 'app_widgets');
    }

    protected function tearDown(): void
    {
        foreach (['Blog', 'Shop'] as $module) {
            if ($this->repository->has($module)) {
                $this->repository->delete($module);
            }
        }
        $this->app['files']->delete(database_path('migrations/'.self::APP_MIGRATION.'.php'));
        parent::tearDown();
    }

    public function test_it_lists_the_migrations_of_the_configured_and_the_registered_paths()
    {
        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);

        $extra = base_path('modules/Blog/database/migrations/feature-flags');
        $this->writeMigration($extra, self::EXTRA_MIGRATION, 'feature_flags');
        $this->app['migrator']->path($extra);

        $code = $this->artisan('module:migrate-status', ['module' => ['Blog']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertMatchesRegularExpression('/'.self::MIGRATION.'.*Ran/', $output);
        $this->assertMatchesRegularExpression('/'.self::EXTRA_MIGRATION.'.*Pending/', $output);
        $this->assertStringNotContainsString(self::APP_MIGRATION, $output);
    }

    public function test_it_lists_the_configured_path_of_a_disabled_module()
    {
        $this->repository->find('Blog')->disable();
        $this->rebootMigrator();
        $this->app[Kernel::class]->call('migrate:install');

        $code = $this->artisan('module:migrate-status', ['module' => ['Blog']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertMatchesRegularExpression('/'.self::MIGRATION.'.*Pending/', $output);
        $this->assertStringNotContainsString(self::APP_MIGRATION, $output);
    }

    public function test_it_fails_without_a_migration_table()
    {
        $code = $this->artisan('module:migrate-status', ['module' => ['Blog']]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Migration table not found.', $output);
    }

    public function test_json_lists_the_ran_and_the_pending_migrations_sorted_by_name()
    {
        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);

        $migrations = base_path('modules/Blog/database/migrations');
        $this->writeMigration($migrations, self::EXTRA_MIGRATION, 'feature_flags');
        $this->writeMigration($migrations, self::EARLY_MIGRATION, 'blog_tags');

        [$code, $document] = $this->statusJson(['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertSame([
            'modules' => [[
                'name' => 'Blog',
                'migrations' => [
                    ['name' => self::EARLY_MIGRATION, 'ran' => false, 'batch' => null],
                    ['name' => self::MIGRATION, 'ran' => true, 'batch' => 1],
                    ['name' => self::EXTRA_MIGRATION, 'ran' => false, 'batch' => null],
                ],
            ]],
            'migration_table' => true,
        ], $document);
    }

    public function test_json_reads_the_batch_of_each_migration()
    {
        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        $this->writeMigration(base_path('modules/Blog/database/migrations'), self::EXTRA_MIGRATION, 'feature_flags');
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);

        [, $document] = $this->statusJson(['module' => ['Blog']]);

        $this->assertSame([
            ['name' => self::MIGRATION, 'ran' => true, 'batch' => 1],
            ['name' => self::EXTRA_MIGRATION, 'ran' => true, 'batch' => 2],
        ], $document['modules'][0]['migrations']);
    }

    public function test_json_lists_all_modules_in_the_given_direction()
    {
        $this->createModule('Shop');
        $this->repository->find('Blog')->json()->set('priority', 1)->save();
        $this->repository->find('Shop')->json()->set('priority', 2)->save();
        $this->app[Kernel::class]->call('migrate:install');

        [$code, $descending] = $this->statusJson(['--all' => true, '--direction' => 'desc']);
        [, $ascending] = $this->statusJson(['--all' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(['Shop', 'Blog'], array_column($descending['modules'], 'name'));
        $this->assertSame(['Blog', 'Shop'], array_column($ascending['modules'], 'name'));
        $this->assertSame([], $descending['modules'][0]['migrations']);
        $this->assertSame(
            [['name' => self::MIGRATION, 'ran' => false, 'batch' => null]],
            $descending['modules'][1]['migrations']
        );
    }

    public function test_json_lists_every_migration_as_pending_without_a_migration_table()
    {
        [$code, $document] = $this->statusJson(['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertSame([
            'modules' => [[
                'name' => 'Blog',
                'migrations' => [['name' => self::MIGRATION, 'ran' => false, 'batch' => null]],
            ]],
            'migration_table' => false,
        ], $document);
    }

    public function test_json_reads_the_migrations_of_the_given_database()
    {
        $this->app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);

        [, $tenant] = $this->statusJson(['module' => ['Blog'], '--database' => 'tenant']);
        [, $default] = $this->statusJson(['module' => ['Blog']]);

        $this->assertTrue($tenant['migration_table']);
        $this->assertSame([['name' => self::MIGRATION, 'ran' => true, 'batch' => 1]], $tenant['modules'][0]['migrations']);
        $this->assertFalse($default['migration_table']);
        $this->assertSame([['name' => self::MIGRATION, 'ran' => false, 'batch' => null]], $default['modules'][0]['migrations']);
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_json_output_is_only_one_line_of_json()
    {
        $this->app[Kernel::class]->call('migrate:install');

        $code = $this->app[Kernel::class]->call('module:migrate-status', ['module' => ['Blog'], '--json' => true]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertStringEndsWith("}\n", $output);
        $this->assertSame(1, substr_count($output, "\n"));
        $this->assertIsArray(json_decode($output, true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_json_output_has_no_ansi_codes_with_ansi()
    {
        $this->app[Kernel::class]->call('migrate:install');

        $this->app[Kernel::class]->call('module:migrate-status', ['module' => ['Blog'], '--json' => true, '--ansi' => true]);
        $output = $this->app[Kernel::class]->output();

        $this->assertStringNotContainsString("\e[", $output);
        $this->assertSame('Blog', json_decode($output, true, flags: JSON_THROW_ON_ERROR)['modules'][0]['name']);
    }

    public function test_json_reports_an_unknown_module_on_stderr_and_lists_no_module()
    {
        $this->app[Kernel::class]->call('migrate:install');
        $output = new MemoryConsoleOutput;

        $input = new ArrayInput(['command' => 'module:migrate-status', 'module' => ['Blog', 'Nope'], '--json' => true]);
        $input->setInteractive(false);
        $code = $this->app[Kernel::class]->handle($input, $output);

        $this->assertSame(1, $code);
        $this->assertSame('{"modules":[],"migration_table":true}'."\n", $output->stdout());
        $this->assertStringContainsString('Module [Nope] does not exist.', $output->stderr());
    }

    /**
     * Run module:migrate-status --json and decode its output.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function statusJson(array $parameters): array
    {
        $code = $this->app[Kernel::class]->call('module:migrate-status', [...$parameters, '--json' => true]);

        return [$code, json_decode($this->app[Kernel::class]->output(), true, flags: JSON_THROW_ON_ERROR)];
    }

    /**
     * Re-resolve the migrator and the artisan commands, so the auto-discovered
     * module migration paths reflect the modules created in setUp().
     */
    private function rebootMigrator(): void
    {
        $this->app->forgetInstance('migrator');
        $this->app[Kernel::class]->setArtisan(null);
    }

    private function writeMigration(string $directory, string $migration, string $table): void
    {
        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put("$directory/$migration.php", <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('$table', fn (Blueprint \$table) => \$table->id());
    }

    public function down(): void
    {
        Schema::dropIfExists('$table');
    }
};
PHP);
    }
}
