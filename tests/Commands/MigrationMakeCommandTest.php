<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class MigrationMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_migration_class()
    {
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $files = $this->finder->allFiles($this->modulePath . '/Database/Migrations');

        $this->assertCount(1, $files);
    }

    /** @test */
    public function it_generates_correct_create_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_correct_add_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'add_something_to_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_correct_delete_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'delete_something_from_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_correct_drop_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'drop_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_correct_default_migration_file_content()
    {
        $this->artisan('module:make-migration', ['name' => 'something_random_name', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Database/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
    }
}
