<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SeedCommand extends Command
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database seeder from the specified module or from all modules.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->module = $this->laravel['modules'];

        $name = $this->argument('module');

        if ($name) {
            if (!$this->module->has(Str::studly($name))) {
                return $this->error("Module [$name] does not exists.");
            }

            $class = $this->getSeederName($name);
            if (class_exists($class)) {
                $this->dbseed($name);

                return $this->info("Module [$name] seeded.");
            } else {
                return $this->error("Class [$class] does not exists.");
            }
        }

        foreach ($this->module->getOrdered() as $module) {
            $name = $module->getName();
            $config = $module->get('migrate');
            if(is_array($config) && array_key_exists('seeds', $config)) {
                foreach((array)$config['seeds'] as $class) {
                    if (class_exists($class)) {
                        $this->dbseed($class);
                    } else {
                        return $this->error("Class [$class] does not exist");
                    }
                }
            } else {
                if (class_exists($this->getSeederName($name))) {
                    $this->dbseed($name);
                }
            }
            $this->info("Module [$name] seeded.");
        }

        return $this->info('All modules seeded.');
    }

    /**
     * Seed the specified module.
     *
     * @parama string  $name
     *
     * @return array
     */
    protected function dbseed($name)
    {
        $params = [
            '--class' => $this->option('class') ?: $this->getSeederName($name),
        ];

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        if ($option = $this->option('force')) {
            $params['--force'] = $option;
        }

        $this->call('db:seed', $params);
    }

    /**
     * Get master database seeder name for the specified module.
     *
     * @param string $name
     *
     * @return string
     */
    public function getSeederName($name)
    {
        $name = Str::studly($name);

        $namespace = $this->laravel['modules']->config('namespace');

        return $namespace . '\\' . $name . '\Database\Seeders\\' . $name . 'DatabaseSeeder';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of module will be used.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', null),
            array('all', null, InputOption::VALUE_NONE, 'Whether or not we should seed all modules.'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
        );
    }
}
