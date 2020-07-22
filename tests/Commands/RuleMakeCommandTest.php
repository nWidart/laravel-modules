<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Contracts\RepositoryInterface;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    /** @test */
    public function it_makes_rule()
    {
        $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $ruleFile = $this->modulePath . '/Rules/UniqueRule.php';

        $this->assertTrue(is_file($ruleFile), 'Rule file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($ruleFile));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.rules.path', 'SuperRules');

        $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/SuperRules/UniqueRule.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.rules.namespace', 'SuperRules');

        $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Rules/UniqueRule.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
