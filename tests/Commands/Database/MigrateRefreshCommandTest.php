<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Commands\Database\MigrateRefreshCommand;
use Nwidart\Modules\Commands\Database\MigrateResetCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\FailingSeeder;
use Nwidart\Modules\Tests\Commands\Database\Fixtures\RecordingSeeder;

class MigrateRefreshCommandTest extends BaseTestCase
{
    private const CONFIRMATION = 'Are you sure you want to run this command?';

    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];

        $this->writeMigration(base_path('modules/Blog/database/migrations'), 'blog_posts');
        $this->useSeeder(RecordingSeeder::class);
        RecordingSeeder::$runs = 0;
        RecordingSeeder::$connections = [];

        $this->rebootMigrator();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        DB::table('blog_posts')->insert(['id' => 1]);
    }

    protected function tearDown(): void
    {
        MigrateRefreshCommand::prohibit(false);
        MigrateResetCommand::prohibit(false);
        $this->app['env'] = 'testing';

        $this->repository->delete('Blog');
        parent::tearDown();
    }

    public function test_it_refreshes_and_seeds_the_module()
    {
        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--seed' => true]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertSame(0, DB::table('blog_posts')->count());
        $this->assertSame(1, RecordingSeeder::$runs);
    }

    public function test_it_refreshes_and_seeds_in_production_after_a_single_confirmation()
    {
        $this->app['env'] = 'production';

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--seed' => true])
            ->expectsConfirmation(self::CONFIRMATION, 'yes')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('blog_posts')->count());
        $this->assertSame(1, RecordingSeeder::$runs);
    }

    public function test_it_does_nothing_in_production_when_the_confirmation_is_declined()
    {
        $this->app['env'] = 'production';

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--seed' => true])
            ->expectsConfirmation(self::CONFIRMATION, 'no')
            ->assertFailed();

        $this->assertSame(1, DB::table('blog_posts')->count());
        $this->assertSame(0, RecordingSeeder::$runs);
    }

    public function test_it_does_nothing_when_prohibited()
    {
        MigrateRefreshCommand::prohibit();

        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--seed' => true]);

        $this->assertSame(1, $code);
        $output = $this->app[Kernel::class]->output();
        $this->assertStringContainsString('This command is prohibited from running in this environment.', $output);
        $this->assertStringNotContainsString('Refreshing Migration Blog module', $output);
        $this->assertSame(1, DB::table('blog_posts')->count());
        $this->assertSame(0, RecordingSeeder::$runs);
    }

    public function test_it_fails_without_resetting_when_only_the_nested_reset_command_is_prohibited()
    {
        MigrateResetCommand::prohibit();

        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--seed' => true]);

        $this->assertSame(1, $code);
        $output = $this->app[Kernel::class]->output();
        // the refresh itself is allowed and starts, its nested module:migrate-reset call refuses
        $this->assertStringContainsString('Refreshing Migration Blog module', $output);
        $this->assertStringContainsString('This command is prohibited from running in this environment.', $output);
        $this->assertSame(1, DB::table('blog_posts')->count());
        $this->assertSame(0, RecordingSeeder::$runs);
    }

    public function test_it_fails_when_the_seeder_fails()
    {
        $this->useSeeder(FailingSeeder::class);

        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--seed' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('FAIL', $this->app[Kernel::class]->output());
    }

    public function test_it_seeds_the_selected_database()
    {
        $this->useTenantConnection();
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog'], '--database' => 'tenant']);

        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog'], '--database' => 'tenant', '--seed' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(['tenant'], RecordingSeeder::$connections);
        $this->assertTrue(Schema::connection('tenant')->hasTable('blog_posts'));
        // the default connection is left alone
        $this->assertSame(1, DB::table('blog_posts')->count());
    }

    private function useTenantConnection(): void
    {
        $this->app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function useSeeder(string $seeder): void
    {
        $this->repository->find('Blog')->json()->set('migration', ['seeds' => [$seeder]])->save();
    }

    public function test_it_refreshes_the_migrations_of_a_path_registered_inside_the_module()
    {
        $extra = base_path('modules/Blog/database/migrations/feature-flags');
        $this->writeMigration($extra, 'feature_flags');
        $this->app['migrator']->path($extra);
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Blog']]);
        DB::table('feature_flags')->insert(['id' => 1]);

        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('feature_flags'));
        $this->assertSame(0, DB::table('feature_flags')->count());
        $this->assertSame(0, DB::table('blog_posts')->count());
    }

    public function test_it_refreshes_only_the_enabled_modules_with_all()
    {
        $this->createDisabledShopModule();

        try {
            $code = $this->artisan('module:migrate-refresh', ['--all' => true, '--no-interaction' => true]);
        } finally {
            $this->repository->delete('Shop');
        }

        $this->assertSame(0, $code);
        $this->assertSame(0, DB::table('blog_posts')->count());
        // the disabled module was left alone
        $this->assertSame(1, DB::table('shop_orders')->count());
    }

    public function test_it_lists_only_the_enabled_modules_in_the_prompt()
    {
        $this->createDisabledShopModule();

        try {
            $this->mockConsoleOutput = true;
            $this->artisan('module:migrate-refresh')
                ->expectsQuestion('Select Modules', '')
                ->expectsChoice('Select Modules', [BaseCommand::ALL], [BaseCommand::ALL, 'blog'])
                ->assertSuccessful();
        } finally {
            $this->repository->delete('Shop');
        }

        $this->assertSame(0, DB::table('blog_posts')->count());
        $this->assertSame(1, DB::table('shop_orders')->count());
    }

    public function test_it_refreshes_a_disabled_module_given_by_name()
    {
        $this->repository->find('Blog')->disable();

        $code = $this->artisan('module:migrate-refresh', ['module' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertTrue(Schema::hasTable('blog_posts'));
        $this->assertSame(0, DB::table('blog_posts')->count());
    }

    /**
     * Create a migrated Shop module with one row in its table, then disable it.
     */
    private function createDisabledShopModule(): void
    {
        $this->createModule('Shop');
        $this->writeMigration(base_path('modules/Shop/database/migrations'), 'shop_orders');
        $this->app[Kernel::class]->call('module:migrate', ['module' => ['Shop']]);
        DB::table('shop_orders')->insert(['id' => 1]);
        $this->repository->find('Shop')->disable();
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
