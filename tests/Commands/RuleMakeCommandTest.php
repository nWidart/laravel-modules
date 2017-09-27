<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RuleMakeCommandTest extends BaseTestCase
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
    public function it_makes_rule()
    {
        $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $ruleFile = $this->modulePath . '/Rules/UniqueRule.php';

        $this->assertTrue(is_file($ruleFile), 'Rule file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($ruleFile));
    }
}
