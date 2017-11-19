<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ModelMakeCommandTest extends BaseTestCase
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
    public function it_generates_a_new_model_class()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Entities/Post.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Entities/Post.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_correct_fillable_fields()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--fillable' => 'title,slug']);

        $file = $this->finder->get($this->modulePath . '/Entities/Post.php');

        $this->assertMatchesSnapshot($file);
    }

    /** @test */
    public function it_generates_migration_file_with_model()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--migration' => true]);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);
    }

    /** @test */
    public function it_generates_migration_file_with_model_using_shortcut_option()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);
    }

    /** @test */
    public function it_generates_correct_migration_file_name_with_multiple_words_model()
    {
        $this->artisan('module:make-model', ['model' => 'ProductDetail', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());

        $this->assertContains('create_product_details_table', $migrationFile->getFilename());
        $this->assertMatchesSnapshot($migrationContent);
    }

    /** @test */
    public function it_displays_error_if_model_already_exists()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertContains('already exists', Artisan::output());
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.model.path', 'Models');

        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Models/Post.php');

        $this->assertMatchesSnapshot($file);
    }
}
