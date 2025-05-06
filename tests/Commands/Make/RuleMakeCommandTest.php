<?php

namespace Nwidart\Modules\Tests\Commands\Make;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_makes_rule()
    {
        $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $ruleFile = $this->module_app_path('Rules/UniqueRule.php');

        $this->assertTrue(is_file($ruleFile), 'Rule file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($ruleFile));
        $this->assertSame(0, $code);
    }

    public function test_it_makes_implicit_rule()
    {
        $code = $this->artisan('module:make-rule', ['name' => 'ImplicitUniqueRule', 'module' => 'Blog', '--implicit' => true]);

        $ruleFile = $this->module_app_path('Rules/ImplicitUniqueRule.php');

        $this->assertTrue(is_file($ruleFile), 'Rule file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($ruleFile));
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.rules.path', 'SuperRules');

        $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('SuperRules/UniqueRule.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.rules.namespace', 'SuperRules');

        $code = $this->artisan('module:make-rule', ['name' => 'UniqueRule', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('Rules/UniqueRule.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
