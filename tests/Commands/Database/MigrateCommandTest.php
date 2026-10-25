<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\RecordingSeeder;

class MigrateCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->createModule('BlogPost');
        $this->repository = $this->app[RepositoryInterface::class];

        $this->writeMigration(base_path('modules/Blog/database/migrations'), 'blog_posts');
        $this->writeMigration(base_path('modules/BlogPost/database/migrations'), 'blog_post_comments');
        $this->writeMigration(database_path('migrations'), 'app_widgets');
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        $this->repository->delete('BlogPost');
        $this->app['files']->delete(database_path('migrations/2020_01_01_000000_create_app_widgets_table.php'));
        parent::tearDown();
    }

    public function test_it_only_runs_the_given_module_migrations()
    {
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('blog_post_comments'));
        $this->assertFalse(Schema::hasTable('app_widgets'));
    }

    public function test_it_does_not_run_app_migrations_for_a_disabled_module()
    {
        $this->repository->find('Blog')->disable();
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('blog_post_comments'));
        $this->assertFalse(Schema::hasTable('app_widgets'));
    }

    public function test_it_migrates_every_module_with_all_without_interaction()
    {
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertTrue(Schema::hasTable('blog_post_comments'));
        $this->assertFalse(Schema::hasTable('app_widgets'));
    }

    public function test_it_fails_without_a_module_when_not_interactive()
    {
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('No module given. Pass a module name or --all.', $this->app[Kernel::class]->output());
        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('blog_post_comments'));
    }

    public function test_it_only_runs_the_migrations_of_the_given_subdirectory()
    {
        $this->writeMigration(base_path('modules/Blog/database/migrations/v2'), 'blog_tags');
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['module' => ['Blog'], '--subpath' => 'v2']);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_tags'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('app_widgets'));
    }

    public function test_it_only_runs_the_given_migration_file()
    {
        $this->writeMigration(base_path('modules/Blog/database/migrations'), 'blog_tags', '2020_01_02_000000');
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', [
            'module' => ['Blog'],
            '--subpath' => '2020_01_02_000000_create_blog_tags_table.php',
        ]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_tags'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_runs_the_migrations_matching_a_glob_subpath()
    {
        $this->writeMigration(base_path('modules/Blog/database/migrations/v2'), 'blog_tags');
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['module' => ['Blog'], '--subpath' => 'v2/*.php']);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_tags'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_fails_when_the_subpath_does_not_exist()
    {
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['module' => ['Blog', 'BlogPost'], '--subpath' => 'missing']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            'Migration subpath [missing] does not exist in module [Blog].',
            $this->app[Kernel::class]->output()
        );
        $this->assertFalse(Schema::hasTable('blog_posts'));
        $this->assertFalse(Schema::hasTable('blog_post_comments'));
    }

    public function test_it_seeds_the_selected_database()
    {
        $this->app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->repository->find('Blog')->json()->set('migration', ['seeds' => [RecordingSeeder::class]])->save();
        RecordingSeeder::$connections = [];
        $this->rebootMigrator();

        $code = $this->artisan('module:migrate', ['module' => ['Blog'], '--database' => 'tenant', '--seed' => true]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));
        $this->assertSame(['tenant'], RecordingSeeder::$connections);
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

    private function writeMigration(string $directory, string $table, string $timestamp = '2020_01_01_000000'): void
    {
        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put("$directory/{$timestamp}_create_{$table}_table.php", <<<PHP
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
