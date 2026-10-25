<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class MigrateResetCommandTest extends BaseTestCase
{
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
        $this->repository->delete('Blog');
        parent::tearDown();
    }

    public function test_it_resets_the_module_migrations()
    {
        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-reset', ['module' => ['Blog']])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged());
    }

    public function test_it_only_shows_the_queries_when_pretending()
    {
        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-reset', ['module' => ['Blog'], '--pretend' => true])
            ->expectsOutputToContain(self::MIGRATION)
            ->expectsOutputToContain('drop table if exists "blog_posts"')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
    }

    public function test_it_resets_the_migrations_on_the_selected_database()
    {
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-reset', ['module' => ['Blog'], '--database' => 'tenant'])
            ->assertSuccessful();

        $this->assertFalse(Schema::connection('tenant')->hasTable('blog_posts'));
        $this->assertFalse($this->migrationIsLogged('tenant'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_it_runs_nothing_on_any_connection_when_pretending_on_another_database()
    {
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-reset', ['module' => ['Blog'], '--database' => 'tenant', '--pretend' => true])
            ->expectsOutputToContain('drop table if exists "blog_posts"')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));
        $this->assertTrue($this->migrationIsLogged());
        $this->assertTrue($this->migrationIsLogged('tenant'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    private function migrationIsLogged(?string $connection = null): bool
    {
        return DB::connection($connection)->table('migrations')->where('migration', self::MIGRATION)->exists();
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
