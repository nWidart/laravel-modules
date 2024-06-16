<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_makes_policy()
    {
        $code = $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

        $policyFile = $this->modulePath.'/Policies/PostPolicy.php';

        $this->assertTrue(is_file($policyFile), 'Policy file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($policyFile));
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.policies.path', 'SuperPolicies');

        $code = $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/SuperPolicies/PostPolicy.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.policies.namespace', 'SuperPolicies');

        $code = $this->artisan('module:make-policy', ['name' => 'PostPolicy', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Policies/PostPolicy.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
