<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Process\Process;

class MigrationMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_migration_class()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $files = $this->finder->allFiles($this->modulePath.'/database/migrations');

        $this->assertCount(1, $files);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_create_migration_file_content()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_add_migration_file_content()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'add_something_to_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_delete_migration_file_content()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'delete_something_from_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_drop_migration_file_content()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'drop_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_default_migration_file_content()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'something_random_name', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_foreign_key_constraints()
    {
        $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog', '--fields' => 'belongsTo:user:id:users']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_valid_create_migration_with_complex_fields()
    {
        $code = $this->artisan('module:make-migration', [
            'name' => 'create_posts_table',
            'module' => 'Blog',
            '--fields' => "title:string:default('Hello World'), amount:decimal(8,2):default(0), starts_at:time:default('12:00'), belongsTo:user:id:users, belongsTo:category:id:categories, soft_delete, remember_token",
        ]);

        $file = $this->getGeneratedMigration();

        $this->assertSame(0, $code);
        $this->assertValidPhp($file);
        $this->assertStringContainsString("\$table->string('title')->default('Hello World');", $file);
        $this->assertStringContainsString("\$table->decimal('amount', 8, 2)->default(0);", $file);
        $this->assertStringContainsString("\$table->foreign('user_id')->references('id')->on('users');", $file);
        $this->assertStringContainsString("\$table->foreign('category_id')->references('id')->on('categories');", $file);
        $this->assertMatchesSnapshot($file);
    }

    public function test_it_generates_a_valid_add_migration_that_reverses_its_fields()
    {
        $code = $this->artisan('module:make-migration', [
            'name' => 'add_author_to_posts_table',
            'module' => 'Blog',
            '--fields' => "belongsTo:user:id:users, belongsTo:category:id:categories, status:string:default('in review'), soft_delete, remember_token",
        ]);

        $file = $this->getGeneratedMigration();

        $this->assertSame(0, $code);
        $this->assertValidPhp($file);
        $this->assertStringContainsString("\$table->dropForeign(['user_id']);", $file);
        $this->assertStringContainsString("\$table->dropForeign(['category_id']);", $file);
        $this->assertStringContainsString('$table->dropSoftDeletes();', $file);
        $this->assertStringContainsString('$table->dropRememberToken();', $file);
        $this->assertMatchesSnapshot($file);
    }

    public function test_it_generates_a_valid_delete_migration_that_reverses_its_fields()
    {
        $code = $this->artisan('module:make-migration', [
            'name' => 'remove_author_from_posts_table',
            'module' => 'Blog',
            '--fields' => 'belongsTo:user:id:users, soft_delete',
        ]);

        $file = $this->getGeneratedMigration();

        $this->assertSame(0, $code);
        $this->assertValidPhp($file);
        $this->assertMatchesSnapshot($file);
    }

    public function test_it_finds_the_table_name_without_a_table_suffix()
    {
        $code = $this->artisan('module:make-migration', [
            'name' => 'add_votes_to_posts',
            'module' => 'Blog',
            '--fields' => 'votes:integer',
        ]);

        $file = $this->getGeneratedMigration();

        $this->assertSame(0, $code);
        $this->assertValidPhp($file);
        $this->assertStringContainsString("Schema::table('posts', function (Blueprint \$table) {", $file);
        $this->assertStringNotContainsString("Schema::table('',", $file);
    }

    private function getGeneratedMigration(): string
    {
        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');

        $this->assertCount(1, $migrations);

        return $this->finder->get($migrations[0]->getPathname());
    }

    private function assertValidPhp(string $contents): void
    {
        $path = tempnam(sys_get_temp_dir(), 'migration').'.php';
        file_put_contents($path, $contents);

        try {
            $process = new Process([PHP_BINARY, '-l', $path]);
            $process->run();

            $this->assertTrue($process->isSuccessful(), 'Generated migration is not valid PHP: '.$process->getOutput().$process->getErrorOutput());
        } finally {
            @unlink($path);
            @unlink(substr($path, 0, -4));
        }
    }
}
