<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

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
