<?php

namespace Nwidart\Modules\Tests\Commands\Database\Fixtures;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecordingSeeder extends Seeder
{
    /**
     * How many times the seeder ran.
     */
    public static int $runs = 0;

    /**
     * The default connection during each run, which db:seed --database switches.
     *
     * @var array<int, string>
     */
    public static array $connections = [];

    public function run(): void
    {
        static::$runs++;
        static::$connections[] = DB::getDefaultConnection();
    }
}
