<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class TestCommand extends Command
{
    use ModuleCommandTrait;

    protected $argumentModule = 'module';
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
	 * @throws \Exception
	 */
    public function handle()
    {
	    $phpunit = exec('which phpunit');
	    if (empty($phpunit)) {
	    	$this->error('phpunit not found on your system');
		    return;
	    }

	    $php = exec('which php');

	    $phpunitxml = base_path('phpunit.xml');
        $testPath = GenerateConfigReader::read('test')->getPath();

	    /** @var \Nwidart\Modules\Module[] $modules */
	    $modules = $this->argument($this->argumentModule) ? [$this->laravel['modules']->findOrFail($this->getModuleName())] : $this->laravel['modules']->getByStatus(1);

	    $descriptorspec = [
		    ['pipe', 'r'],
	    ];

	    $process = null;

        foreach ($modules as $module) {
	        $directory = $module->getPath().DIRECTORY_SEPARATOR.$testPath;
	        if (! file_exists($directory)) {
	        	continue;
	        }

	        if (! File::glob($directory.DIRECTORY_SEPARATOR.'*.php')) {
	        	continue;
	        }

	        $this->info("Running tests for module {$module}:");
	        $process = proc_open("{$php} {$phpunit} -c {$phpunitxml} {$directory}", $descriptorspec, $pipes);
        }
        if (is_resource($process)) {
	        proc_close($process);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [$this->argumentModule, InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }
}
