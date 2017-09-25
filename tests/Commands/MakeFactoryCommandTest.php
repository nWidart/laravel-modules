<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class MakeFactoryCommandTest extends BaseTestCase
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
    public function it_generates_a_new_factory_class()
    {
        $this->artisan('module:make-factory', ['name' => 'PostFactory', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Database/factories/PostFactory.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-factory', ['name' => 'PostFactory', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Database/factories/PostFactory.php');
        
        $this->assertEquals($this->expectedContent(), $file);
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

use Faker\Generator as Faker;

\$factory->define(Model::class, function (Faker \$faker) {
    return [
        //
    ];
});

TEXT;
    }
}
