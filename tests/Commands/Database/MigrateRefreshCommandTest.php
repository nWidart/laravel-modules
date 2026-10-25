<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
