<?php return '<?php

namespace Modules\\Blog\\Console;

use Illuminate\\Console\\Command;
use Symfony\\Component\\Console\\Input\\InputOption;
use Symfony\\Component\\Console\\Input\\InputArgument;

class MyAwesomeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = \'my:awesome\';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = \'Command description.\';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [\'example\', InputArgument::REQUIRED, \'An example argument.\'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [\'example\', null, InputOption::VALUE_OPTIONAL, \'An example option.\', null],
        ];
    }
}
';
