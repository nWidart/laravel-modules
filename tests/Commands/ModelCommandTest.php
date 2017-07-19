<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class ModelCommandTest extends BaseTestCase
{
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

        $this->assertEquals($this->expectedContent(), $file);
    }

    /** @test */
    public function it_generates_correct_fillable_fields()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--fillable' => 'title,slug']);

        $file = $this->finder->get($this->modulePath . '/Entities/Post.php');

        $this->assertTrue(str_contains($file, "protected \$fillable = [\"title\",\"slug\"];"));
    }

    /** @test */
    public function it_generates_migration_file_with_model()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--migration' => true]);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertContains('Schema::create(\'posts\',', $migrationContent);
    }

    /** @test */
    public function it_generates_migration_file_with_model_using_shortcut_option()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->modulePath . '/Database/Migrations/' . $migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertContains('Schema::create(\'posts\',', $migrationContent);
    }

    /** @test */
    public function it_generates_correct_migration_file_name_with_multiple_words_model()
    {
        $this->artisan('module:make-model', ['model' => 'ProductDetail', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->modulePath . '/Database/Migrations');
        $migrationFile = $migrations[0];

        $this->assertContains('create_product_details_table', $migrationFile->getFilename());
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Entities;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected \$fillable = [];
}

TEXT;
    }
}
