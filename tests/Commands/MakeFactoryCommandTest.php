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
    public function it_makes_factory()
    {
        $this->artisan('module:make-factory', ['name' => 'PostFactory', 'module' => 'Blog']);
        
        $factoryFile = $this->modulePath . '/Database/factories/PostFactory.php';

        $this->assertTrue(is_file($factoryFile), 'Factory file was not created.');   
        $this->assertEquals($this->expectedContent(), $this->finder->get($factoryFile), 'Content of factory file is not correct.');
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
