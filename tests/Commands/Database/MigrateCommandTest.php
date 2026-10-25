<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

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
