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
