<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class UnUseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:unuse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forget the used module with module:use';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->laravel['modules']->forgetUsed();

        $this->info('Previous module used successfully forgotten.');
    }
}
