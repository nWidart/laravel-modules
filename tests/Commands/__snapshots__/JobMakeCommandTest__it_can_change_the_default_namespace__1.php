<?php return '<?php

namespace Modules\Blog\SuperJobs;

use Illuminate\\Bus\\Queueable;
use Illuminate\\Queue\\SerializesModels;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;

class SomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
