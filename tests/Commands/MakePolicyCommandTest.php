<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class MakePolicyCommandTest extends BaseTestCase
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
    public function it_makes_policy()
    {
        $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

        $policyFile = $this->modulePath . '/Policies/PostPolicy.php';

        $this->assertTrue(is_file($policyFile), 'Policy file was not created.');
        $this->assertEquals($this->expectedContent(), $this->finder->get($policyFile), 'Content of policy file is not correct.');
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}

TEXT;
    }
}
