<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class PolicyMakeCommandTest extends BaseTestCase
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
    public function it_makes_policy()
    {
        $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

        $policyFile = $this->modulePath . '/Policies/PostPolicy.php';

        $this->assertTrue(is_file($policyFile), 'Policy file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($policyFile));
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.policies.path', 'SuperPolicies');

        $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/SuperPolicies/PostPolicy.php');

        $this->assertMatchesSnapshot($file);
    }
}
