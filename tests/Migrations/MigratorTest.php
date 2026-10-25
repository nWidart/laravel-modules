<?php

namespace Nwidart\Modules\Tests\Migrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Migrations\Fixtures\TransactionalSQLiteGrammar;

class MigratorTest extends BaseTestCase
{
    private const MIGRATION = '2020_01_01_000000_create_blog_posts_table';

    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];

        $this->app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        parent::tearDown();
    }

    public function test_up_runs_on_the_default_connection()
    {
        $this->writeMigration();

        $this->migrator()->up(self::MIGRATION);

        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_up_runs_on_the_selected_database()
    {
        $this->writeMigration();

        $this->migrator()->setDatabase('tenant')->up(self::MIGRATION);

        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_up_runs_inside_a_transaction_when_the_grammar_supports_it()
    {
        $this->supportSchemaTransactions();
        $this->writeMigration(fail: true, withinTransaction: true);

        try {
            $this->migrator()->up(self::MIGRATION);
            $this->fail('The migration should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Migrating failed on purpose.', $e->getMessage());
        }

        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertSame('sqlite', DB::getDefaultConnection());
    }

    public function test_up_does_not_use_a_transaction_when_the_migration_opts_out()
    {
        $this->supportSchemaTransactions();
        $this->writeMigration(fail: true, withinTransaction: false);

        try {
            $this->migrator()->up(self::MIGRATION);
            $this->fail('The migration should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Migrating failed on purpose.', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_up_does_not_use_a_transaction_when_the_grammar_does_not_support_it()
    {
        $this->writeMigration(fail: true, withinTransaction: true);

        try {
            $this->migrator()->up(self::MIGRATION);
            $this->fail('The migration should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Migrating failed on purpose.', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    private function supportSchemaTransactions(): void
    {
        $connection = DB::connection();
        $connection->setSchemaGrammar(new TransactionalSQLiteGrammar($connection));
    }

    public function test_find_returns_only_the_rows_of_the_given_migration()
    {
        $repository = $this->app['migration.repository'];
        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $migrator = $this->migrator();
        $migrator->log(self::MIGRATION);
        $migrator->log('2020_01_02_000000_create_blog_tags_table');

        $this->assertSame(1, $migrator->find(self::MIGRATION)->count());
        $this->assertSame(self::MIGRATION, $migrator->find(self::MIGRATION)->value('migration'));
        $this->assertSame(0, $migrator->find('2020_01_03_000000_unknown')->count());
    }

    public function test_get_paths_returns_the_configured_path_and_the_registered_paths_inside_the_module()
    {
        $configured = base_path('modules/Blog/database/migrations');
        $extra = $configured.'/feature-flags';
        $file = base_path('modules/Blog/database/other/2020_01_01_000000_create_blog_tags_table.php');

        foreach ([$extra, base_path('modules/BlogExtra/database/migrations'), base_path('database/other-migrations'), $file, $configured] as $path) {
            $this->app['migrator']->path($path);
        }

        $this->assertSame([$configured, $extra, $file], $this->migrator()->getPaths());
    }

    private function migrator(): Migrator
    {
        return new Migrator($this->repository->find('Blog'), $this->app);
    }

    private function writeMigration(bool $fail = false, bool $withinTransaction = true): void
    {
        $within = $withinTransaction ? 'true' : 'false';
        $throw = $fail ? "throw new RuntimeException('Migrating failed on purpose.');" : '';

        $directory = base_path('modules/Blog/database/migrations');
        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put($directory.'/'.self::MIGRATION.'.php', <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public \$withinTransaction = $within;

    public function up(): void
    {
        Schema::create('blog_posts', fn (Blueprint \$table) => \$table->id());

        $throw
    }
};
PHP);
    }
}
