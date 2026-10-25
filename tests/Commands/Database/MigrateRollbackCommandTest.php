<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Migrations\Fixtures\TransactionalSQLiteGrammar;
use PHPUnit\Framework\Attributes\DataProvider;

class MigrateRollbackCommandTest extends BaseTestCase
{
    private const CONFIRMATION = 'Are you sure you want to run this command?';

    private const MIGRATION = '2020_01_01_000000_create_blog_posts_table';

    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];

        $this->writeMigration(base_path('modules/Blog/database/migrations'), 'blog_posts');

        $this->app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
    }

    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';

        $this->repository->delete('Blog');

        if ($this->repository->has('Shop')) {
            $this->repository->delete('Shop');
        }
        $this->app['files']->deleteDirectory(base_path('modules/BlogExtra'));
        $this->app['files']->deleteDirectory(base_path('database/other-migrations'));
        parent::tearDown();
    }

    public function test_it_rolls_back_the_module_migrations()
    {
        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog']])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged());
    }

    public function test_it_only_shows_the_queries_when_pretending()
    {
        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog'], '--pretend' => true])
            ->expectsOutputToContain(self::MIGRATION)
            ->expectsOutputToContain('drop table if exists "blog_posts"')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
    }

    public function test_it_rolls_back_the_migrations_on_the_selected_database()
    {
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog'], '--database' => 'tenant'])
            ->assertSuccessful();

        $this->assertFalse(Schema::connection('tenant')->hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged('tenant'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_restores_the_default_connection_when_a_migration_fails()
    {
        $this->app['files']->put(base_path('modules/Blog/database/migrations/2020_01_02_000000_fail_on_rollback.php'), <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void {}

    public function down(): void
    {
        throw new RuntimeException('Rolling back on '.DB::getDefaultConnection());
    }
};
PHP);
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);

        try {
            $this->app[Kernel::class]->call('module:migrate-rollback', ['module' => ['Blog'], '--database' => 'tenant']);
            $this->fail('The rollback should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Rolling back on tenant', $e->getMessage());
        }

        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_runs_nothing_on_any_connection_when_pretending_on_another_database()
    {
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog'], '--database' => 'tenant', '--pretend' => true])
            ->expectsOutputToContain('drop table if exists "blog_posts"')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
        $this->assertTrue($this->migrationIsLogged('tenant'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_does_nothing_in_production_when_the_confirmation_is_declined()
    {
        $this->inProduction();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog']])
            ->expectsConfirmation(self::CONFIRMATION, 'no')
            ->assertFailed();

        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
    }

    public function test_it_rolls_back_in_production_after_the_confirmation()
    {
        $this->inProduction();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog']])
            ->expectsConfirmation(self::CONFIRMATION, 'yes')
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged());
    }

    public function test_it_rolls_back_in_production_without_prompting_when_forced()
    {
        $this->inProduction();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['module' => ['Blog'], '--force' => true])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged());
    }

    public function test_it_rolls_back_all_modules_in_descending_priority()
    {
        $this->createModule('Shop');
        $this->writeMigration(base_path('modules/Shop/database/migrations'), 'shop_orders');
        $this->repository->find('Blog')->json()->set('priority', 1)->save();
        $this->repository->find('Shop')->json()->set('priority', 2)->save();
        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Shop']]);

        $code = $this->artisan('module:migrate-rollback', ['--all' => true]);
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('shop_orders'));
        $this->assertLessThan(
            strpos($output, self::MIGRATION),
            strpos($output, '2020_01_01_000000_create_shop_orders_table'),
            'The higher priority module should be rolled back first.'
        );
    }

    public function test_it_rolls_back_every_module_with_all_without_interaction()
    {
        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-rollback', ['--all' => true, '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged());
    }

    public function test_it_rolls_back_a_failing_down_inside_a_transaction()
    {
        $this->supportSchemaTransactions();
        $this->writeFailingRollback(withinTransaction: true);
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        $this->assertTrue(Schema::hasTable('blog_tags'));

        try {
            $this->app[Kernel::class]->call('module:migrate-rollback', ['module' => ['Blog']]);
            $this->fail('The rollback should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Rolling back failed on purpose.', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('blog_tags'));
    }

    public function test_it_does_not_use_a_transaction_when_the_migration_opts_out()
    {
        $this->supportSchemaTransactions();
        $this->writeFailingRollback(withinTransaction: false);
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        $this->assertTrue(Schema::hasTable('blog_tags'));

        try {
            $this->app[Kernel::class]->call('module:migrate-rollback', ['module' => ['Blog']]);
            $this->fail('The rollback should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Rolling back failed on purpose.', $e->getMessage());
        }

        $this->assertFalse(Schema::hasTable('blog_tags'));
    }

    public function test_it_fails_without_a_module_when_not_interactive()
    {
        $code = $this->artisan('module:migrate-rollback', ['--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    private function supportSchemaTransactions(): void
    {
        $connection = DB::connection();
        $connection->setSchemaGrammar(new TransactionalSQLiteGrammar($connection));
    }

    private function writeFailingRollback(bool $withinTransaction): void
    {
        $within = $withinTransaction ? 'true' : 'false';

        $this->app['files']->put(base_path('modules/Blog/database/migrations/2020_01_02_000000_create_blog_tags_table.php'), <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

return new class extends Migration
{
    public \$withinTransaction = $within;

    public function up(): void
    {
        Schema::create('blog_tags', fn (Blueprint \$table) => \$table->id());
    }

    public function down(): void
    {
        Schema::drop('blog_tags');

        throw new RuntimeException('Rolling back failed on purpose.');
    }
};
PHP);
    }

    public function test_it_rolls_back_nothing_when_a_module_does_not_exist()
    {
        try {
            $this->artisan('module:migrate-rollback', ['module' => ['Blog', 'Typo']]);
            $this->fail('An unknown module should have thrown.');
        } catch (ModuleNotFoundException $e) {
            $this->assertStringContainsString('Typo', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
    }

    #[DataProvider('subpaths')]
    public function test_it_rolls_back_the_migrations_of_the_given_subpath(string $subpath)
    {
        $this->writeMigration(base_path('modules/Blog/database/migrations/v2'), 'blog_tags');
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--subpath' => 'v2']);
        $this->assertTrue(Schema::hasTable('blog_tags'));

        $code = $this->artisan('module:migrate-rollback', ['module' => ['Blog'], '--subpath' => $subpath]);

        $this->assertSame(0, $code);
        $this->assertFalse(Schema::hasTable('blog_tags'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
    }

    public static function subpaths(): array
    {
        return [
            'directory' => ['v2'],
            'file' => ['v2/2020_01_01_000000_create_blog_tags_table.php'],
            'glob pattern' => ['v2/*.php'],
        ];
    }

    public function test_it_fails_when_the_subpath_does_not_exist()
    {
        $code = $this->artisan('module:migrate-rollback', ['module' => ['Blog'], '--subpath' => 'missing']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            'Migration subpath [missing] does not exist in module [Blog].',
            $this->app[Kernel::class]->output()
        );
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_it_rolls_back_the_migrations_of_a_path_registered_inside_the_module()
    {
        $extra = base_path('modules/Blog/database/migrations/feature-flags');
        $this->writeMigration($extra, 'feature_flags');
        $this->app['migrator']->path($extra);
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        $this->assertTrue(Schema::hasTable('feature_flags'));

        $code = $this->artisan('module:migrate-rollback', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('2020_01_01_000000_create_feature_flags_table', $this->app[Kernel::class]->output());
        $this->assertFalse(Schema::hasTable('feature_flags'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
    }

    public function test_it_rolls_back_a_migration_file_registered_inside_the_module()
    {
        $extra = base_path('modules/Blog/database/migrations/feature-flags');
        $this->writeMigration($extra, 'feature_flags');
        $this->app['migrator']->path($extra.'/2020_01_01_000000_create_feature_flags_table.php');
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        $this->assertTrue(Schema::hasTable('feature_flags'));

        $code = $this->artisan('module:migrate-rollback', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertFalse(Schema::hasTable('feature_flags'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_it_does_not_roll_back_registered_paths_outside_the_module()
    {
        $extra = base_path('modules/Blog/database/migrations/feature-flags');
        $sibling = base_path('modules/BlogExtra/database/migrations');
        $outside = base_path('database/other-migrations');
        $this->writeMigration($extra, 'feature_flags');
        $this->writeMigration($sibling, 'blog_extra_notes');
        $this->writeMigration($outside, 'app_widgets');
        foreach ([$extra, $sibling, $outside] as $path) {
            $this->app['migrator']->path($path);
        }
        // one batch, so a rollback of the module could reach the other paths too
        $this->app[Kernel::class]->call('migrate', ['--path' => [$extra, $sibling, $outside], '--realpath' => true]);

        $code = $this->artisan('module:migrate-rollback', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertFalse(Schema::hasTable('feature_flags'));
        $this->assertTrue(Schema::hasTable('blog_extra_notes'));
        $this->assertTrue(Schema::hasTable('app_widgets'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    private function migrationIsLogged(?string $connection = null): bool
    {
        return DB::connection($connection)->table('migrations')->where('migration', self::MIGRATION)->exists();
    }

    /**
     * Switch to production after setUp() has scanned the modules. Prompts already
     * fall back to the mockable console output, which stays on once enabled.
     */
    private function inProduction(): void
    {
        $this->app['env'] = 'production';
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

    private function writeMigration(string $directory, string $table): void
    {
        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put("$directory/2020_01_01_000000_create_{$table}_table.php", <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

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
