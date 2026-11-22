<?php

namespace Nwidart\Modules\Tests\Actuator\Concerns;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Nwidart\Modules\Tests\Actuator\Fixtures\PaymentsHealthIndicator;

trait RegistersModuleProviders
{
    /**
     * Register a Blog module provider that only registers its health indicators.
     *
     * @param  array<string, mixed>  $indicators
     */
    private function registerModuleProvider(array $indicators = [
        'payments' => PaymentsHealthIndicator::class,
        'payments.eu' => [PaymentsHealthIndicator::class, ['gateway' => 'adyen']],
    ]): void
    {
        $provider = new class($this->app) extends ModuleServiceProvider
        {
            protected string $name = 'Blog';

            protected string $nameLower = 'blog';

            public function register(): void {}

            public function boot(): void
            {
                $this->registerHealthIndicators();
            }

            /**
             * @param  array<string, mixed>  $indicators
             */
            public function withIndicators(array $indicators): static
            {
                $this->healthIndicators = $indicators;

                return $this;
            }
        };

        $this->app->register($provider->withIndicators($indicators));
    }
}
