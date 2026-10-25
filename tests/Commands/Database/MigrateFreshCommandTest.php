<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Commands\Database\MigrateFreshCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Tests\BaseTestCase;

class MigrateFreshCommandTest extends BaseTestCase
{
    private const CONFIRMATION = 'Are you sure you want to run this command?';

    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];

        $this->writeMigration(base_path('modules/Blog/database/migrations'), 'blog_posts');

        Schema::create('legacy_records', fn (Blueprint $table) => $table->id());

        $this->rebootMigrator();
    }

    protected function tearDown(): void
    {
        MigrateFreshCommand::prohibit(false);
        $this->app['env'] = 'testing';

        $this->repository->delete('Blog');
        parent::tearDown();
    }

    public function test_it_does_nothing_in_production_when_the_confirmation_is_declined()
    {
        $this->inProduction();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-fresh', ['module' => ['Blog']])
            ->expectsConfirmation(self::CONFIRMATION, 'no')
            ->assertFailed();

        $this->assertTrue(Schema::hasTable('legacy_records'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_runs_in_production_after_a_single_confirmation()
    {
        $this->inProduction();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-fresh', ['module' => ['Blog']])
            ->expectsConfirmation(self::CONFIRMATION, 'yes')
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('legacy_records'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_it_runs_in_production_without_prompting_when_forced()
    {
        $this->inProduction();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-fresh', ['module' => ['Blog'], '--force' => true])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('legacy_records'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_it_runs_without_prompting_outside_production()
    {
        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-fresh', ['module' => ['Blog']])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('legacy_records'));
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_it_does_nothing_when_prohibited()
    {
        MigrateFreshCommand::prohibit();

        $this->mockConsoleOutput = true;
        $this->artisan('module:migrate-fresh', ['module' => ['Blog'], '--force' => true])
            ->assertFailed();

        $this->assertTrue(Schema::hasTable('legacy_records'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_does_not_wipe_the_database_when_a_module_does_not_exist()
    {
        try {
            $this->artisan('module:migrate-fresh', ['module' => ['Blog', 'Unknown']]);
            $this->fail('An unknown module should have thrown.');
        } catch (ModuleNotFoundException $e) {
            $this->assertStringContainsString('Unknown', $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('legacy_records'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_does_not_wipe_the_database_without_a_module_when_not_interactive()
    {
        $code = $this->artisan('module:migrate-fresh', ['--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('No module given. Pass a module name or --all.', $this->app[Kernel::class]->output());
        $this->assertTrue(Schema::hasTable('legacy_records'));
    }

    public function test_it_refuses_to_pretend_and_drops_nothing()
    {
        $code = $this->artisan('module:migrate-fresh', ['module' => ['Blog'], '--pretend' => true, '--force' => true]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            '--pretend is not supported by module:migrate-fresh: it would still drop all tables.',
            $this->app[Kernel::class]->output()
        );
        $this->assertTrue(Schema::hasTable('legacy_records'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
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
