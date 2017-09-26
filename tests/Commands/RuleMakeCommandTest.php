<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class RuleMakeCommandTest extends BaseTestCase
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
    public function it_makes_rule()
    {
        $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $ruleFile = $this->modulePath . '/Rules/UniqueRule.php';

        $this->assertTrue(is_file($ruleFile), 'Rule file was not created.');
        $this->assertEquals($this->expectedContent(), $this->finder->get($ruleFile), 'Content of rule file is not correct.');
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Rules;

use Illuminate\Contracts\Validation\Rule;

class UniqueRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  \$attribute
     * @param  mixed  \$value
     * @return bool
     */
    public function passes(\$attribute, \$value)
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}

TEXT;
    }
}
