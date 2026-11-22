<?php

namespace Nwidart\Modules\Tests\Actuator\Fixtures;

use Nwidart\Actuator\Health\Health;
use Nwidart\Actuator\Health\HealthIndicator;

class PaymentsHealthIndicator implements HealthIndicator
{
    public function __construct(private string $gateway = 'stripe') {}

    public function health(): Health
    {
        return Health::up(['gateway' => $this->gateway]);
    }
}
