<?php return '<?php

namespace Modules\\Blog\\Jobs;

use Illuminate\\Bus\\Queueable;
use Illuminate\\Foundation\\Bus\\Dispatchable;

class SomeJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
';
