<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_it_studly_cases_every_segment_of_a_nested_lowercase_name()
    {
        $code = $this->artisan('module:make-health-indicator', ['name' => 'api/v2/Payments', 'module' => 'Blog']);

        $path = $this->modulePath.'/Health/Api/V2/Payments.php';
        $this->assertSame(0, $code);
        $this->assertFileExists($path);
        // Compare the folder names as written, also on a case-insensitive filesystem.
        $this->assertSame(['Api'], array_values(array_diff(scandir($this->modulePath.'/Health'), ['.', '..'])));
        $this->assertSame(['V2'], array_values(array_diff(scandir($this->modulePath.'/Health/Api'), ['.', '..'])));
        $file = $this->finder->get($path);
        $this->assertStringContainsString('namespace Modules\\Blog\\Health\\Api\\V2;', $file);
        $this->assertStringContainsString('class Payments ', $file);
        $this->assertStringContainsString("'payments' => \\Modules\\Blog\\Health\\Api\\V2\\Payments::class,", Artisan::output());
    }

    public function test_a_folder_named_like_the_class_stays_in_the_namespace()
    {
        $code = $this->artisan('module:make-health-indicator', ['name' => 'Payments/Payments', 'module' => 'Blog']);

        $this->assertSame(0, $code);
        $file = $this->finder->get($this->modulePath.'/Health/Payments/Payments.php');
        $this->assertStringContainsString('namespace Modules\\Blog\\Health\\Payments;', $file);
        $this->assertStringContainsString("'payments' => \\Modules\\Blog\\Health\\Payments\\Payments::class,", Artisan::output());
    }

    public static function appFolders(): array
    {
        return [
            'app/' => ['app/', 'app/Health'],
            'domain/' => ['domain/', 'domain/Health'],
            'empty' => ['', 'Health'],
        ];
    }

    #[DataProvider('appFolders')]
    public function test_without_a_health_generator_config_the_path_and_namespace_follow_the_app_folder(string $appFolder, string $folder)
    {
        $this->assertNull($this->app['config']->get('modules.paths.generator.health'));
        $this->app['config']->set('modules.paths.app_folder', $appFolder);

        $code = $this->artisan('module:make-health-indicator', ['name' => 'PaymentsHealthIndicator', 'module' => 'Blog']);

        $this->assertSame(0, $code);
        $file = $this->finder->get($this->getModuleBasePath().'/'.$folder.'/PaymentsHealthIndicator.php');
        $this->assertStringContainsString('namespace Modules\\Blog\\Health;', $file);
    }
}
