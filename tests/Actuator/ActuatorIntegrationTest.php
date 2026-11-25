<?php

namespace Nwidart\Modules\Tests\Actuator;

use Illuminate\Foundation\Application;
use Nwidart\Actuator\ActuatorServiceProvider;
use Nwidart\Actuator\Health\Indicators\PingHealthIndicator;
use Nwidart\Modules\Actuator\ModulesHealthIndicator;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Tests\Actuator\Concerns\RegistersModuleProviders;
use Nwidart\Modules\Tests\Actuator\Fixtures\PaymentsHealthIndicator;
use Nwidart\Modules\Tests\BaseTestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class ActuatorIntegrationTest extends BaseTestCase
{
    use RegistersModuleProviders;

    protected function getPackageProviders($app)
    {
        return [
            LaravelModulesServiceProvider::class,
            ActuatorServiceProvider::class,
        ];
    }

    public function test_it_registers_the_modules_indicator()
    {
        $this->assertSame(ModulesHealthIndicator::class, config('actuator.health.indicators')['modules']);
    }

    public function test_it_keeps_the_actuator_indicators()
    {
        $this->assertArrayHasKey('ping', config('actuator.health.indicators'));
    }

    #[DefineEnvironment('disableIntegration')]
    public function test_it_registers_no_modules_indicator_when_the_integration_is_disabled()
    {
        $this->assertArrayNotHasKey('modules', config('actuator.health.indicators'));
    }

    #[DefineEnvironment('removeIntegrationConfig')]
    public function test_a_missing_actuator_config_key_enables_the_integration()
    {
        $this->assertNull(config('modules.actuator'));
        $this->assertSame(ModulesHealthIndicator::class, config('actuator.health.indicators')['modules']);
    }

    #[DefineEnvironment('defineApplicationIndicators')]
    public function test_an_application_modules_indicator_wins()
    {
        $this->assertSame(PingHealthIndicator::class, config('actuator.health.indicators')['modules']);
    }

    public function test_a_module_provider_registers_its_indicators_under_the_module_name()
    {
        $this->registerModuleProvider();

        $indicators = config('actuator.health.indicators');

        $this->assertSame(PaymentsHealthIndicator::class, $indicators['module:blog:payments']);
        $this->assertSame([PaymentsHealthIndicator::class, ['gateway' => 'adyen']], $indicators['module:blog:payments.eu']);
        $this->assertArrayHasKey('modules', $indicators);
        $this->assertArrayHasKey('ping', $indicators);
    }

    public function test_a_module_provider_registers_no_indicators_when_the_integration_is_disabled()
    {
        config(['modules.actuator.enabled' => false]);

        $this->registerModuleProvider();

        $this->assertSame([], preg_grep('/^module:/', array_keys(config('actuator.health.indicators'))));
    }

    public function test_an_application_indicator_wins_over_a_module_indicator_with_the_same_name()
    {
        config(['actuator.health.indicators' => ['module:blog:payments' => PingHealthIndicator::class]]);

        $this->registerModuleProvider();

        $indicators = config('actuator.health.indicators');

        $this->assertSame(PingHealthIndicator::class, $indicators['module:blog:payments']);
        $this->assertArrayHasKey('module:blog:payments.eu', $indicators);
    }

    #[DefineEnvironment('showOnlyPing')]
    public function test_the_health_endpoint_reports_the_modules_and_the_module_indicators()
    {
        $this->createModule('Blog');

        try {
            $this->registerModuleProvider();

            $response = $this->getJson('/actuator/health');
        } finally {
            $this->app[RepositoryInterface::class]->delete('Blog');
        }

        $response->assertOk();
        $this->assertSame([
            'status' => 'UP',
            'components' => [
                'ping' => ['status' => 'UP'],
                'modules' => [
                    'status' => 'UP',
                    'details' => ['modules' => ['Blog' => ['enabled' => true]]],
                ],
                'module:blog:payments' => [
                    'status' => 'UP',
                    'details' => ['gateway' => 'stripe'],
                ],
                'module:blog:payments.eu' => [
                    'status' => 'UP',
                    'details' => ['gateway' => 'adyen'],
                ],
            ],
        ], $this->withoutDuration($response->json()));
    }

    #[DefineEnvironment('showOnlyPing')]
    public function test_the_health_endpoint_reports_a_single_module_indicator()
    {
        $this->registerModuleProvider();

        $response = $this->getJson('/actuator/health/module:blog:payments')->assertOk();
        $this->assertSame(['status' => 'UP', 'details' => ['gateway' => 'stripe']], $this->withoutDuration($response->json()));

        $response = $this->getJson('/actuator/health/module:blog:payments.eu')->assertOk();
        $this->assertSame(['status' => 'UP', 'details' => ['gateway' => 'adyen']], $this->withoutDuration($response->json()));
    }

    /**
     * The health JSON without the "duration" detail that laravel-actuator 1.4 adds to every indicator,
     * so the assertions do not depend on timing. A "details" left empty is removed.
     *
     * @param  array<string, mixed>  $health
     * @return array<string, mixed>
     */
    private function withoutDuration(array $health): array
    {
        unset($health['details']['duration']);

        if (($health['details'] ?? null) === []) {
            unset($health['details']);
        }

        foreach ($health['components'] ?? [] as $name => $component) {
            $health['components'][$name] = $this->withoutDuration($component);
        }

        return $health;
    }

    /**
     * @param  Application  $app
     */
    protected function disableIntegration($app): void
    {
        $app['config']->set('modules.actuator.enabled', false);
    }

    /**
     * An application with a config/modules.php published before the actuator key existed.
     *
     * @param  Application  $app
     */
    protected function removeIntegrationConfig($app): void
    {
        $modules = $app['config']->get('modules');
        unset($modules['actuator']);
        $app['config']->set('modules', $modules);
    }

    /**
     * @param  Application  $app
     */
    protected function defineApplicationIndicators($app): void
    {
        $app['config']->set('actuator.health.indicators', ['modules' => PingHealthIndicator::class]);
    }

    /**
     * @param  Application  $app
     */
    protected function showOnlyPing($app): void
    {
        $app['config']->set('actuator.health.show_details', 'always');
        $app['config']->set('actuator.health.indicators', ['ping' => PingHealthIndicator::class]);
        $app['config']->set('actuator.health.groups', []);
    }
}
