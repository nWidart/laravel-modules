<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class HealthIndicatorMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;

    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_health_indicator_class()
    {
        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Health/PaymentsHealthIndicator.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Health/PaymentsHealthIndicator.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_prints_how_to_register_the_indicator()
    {
        $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $output = Artisan::output();

        $this->assertStringContainsString('Register it as [module:blog:payments] in the $healthIndicators property of the module service provider:', $output);
        $this->assertStringContainsString("'payments' => \\Modules\\Blog\\Health\\PaymentsHealthIndicator::class,", $output);
    }

    public function test_the_hint_keeps_a_name_without_the_suffix()
    {
        $this->artisan('module:make-health-indicator', ['name' => 'Api\\PaymentGateway', 'module' => 'Blog']);

        $output = Artisan::output();

        $this->assertStringContainsString('[module:blog:paymentGateway]', $output);
        $this->assertStringContainsString("'paymentGateway' => \\Modules\\Blog\\Health\\Api\\PaymentGateway::class,", $output);
    }

    public function test_it_prints_no_hint_when_the_file_already_exists()
    {
        $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);
        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringNotContainsString('Register it as', Artisan::output());
    }

    public function test_it_can_override_with_force_option()
    {
        $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);
        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Health/PaymentsHealthIndicator.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_health_indicator_in_sub_namespace_with_correct_generated_file()
    {
        $code = $this->artisan('module:make-health-indicator', ['name' => 'Api\\PaymentsHealthIndicator', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Health/Api/PaymentsHealthIndicator.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.health.path', 'app/Monitoring/Health');

        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/app/Monitoring/Health/PaymentsHealthIndicator.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Monitoring\\Health;', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.health.namespace', 'Support\\Health');

        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Health/PaymentsHealthIndicator.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Support\\Health;', $file);
        $this->assertStringContainsString("'payments' => \\Modules\\Blog\\Support\\Health\\PaymentsHealthIndicator::class,", Artisan::output());
        $this->assertSame(0, $code);
    }
}
