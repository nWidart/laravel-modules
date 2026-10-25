<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class MigrateStatusCommandTest extends BaseTestCase
{
    private const MIGRATION = '2020_01_01_000000_create_blog_posts_table';

    private const EXTRA_MIGRATION = '2020_01_02_000000_create_feature_flags_table';

    private const APP_MIGRATION = '2020_01_03_000000_create_app_widgets_table';

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
        $this->repository->delete('Blog');
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
