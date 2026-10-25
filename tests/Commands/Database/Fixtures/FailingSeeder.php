<?php

namespace Nwidart\Modules\Tests\Commands\Database\Fixtures;

use Illuminate\Database\Seeder;
use RuntimeException;

class FailingSeeder extends Seeder
{
    public function run(): void
    {
        throw new RuntimeException('Seeding failed on purpose.');
    }
}
