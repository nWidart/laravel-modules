<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Repository;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use RuntimeException;
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
        try {
            if ($name = $this->argument('module')) {
                $name = Str::studly($name);
                $this->moduleSeed($this->getModuleByName($name));
            } else {
                $modules = $this->getModuleRepository()->getOrdered();
                array_walk($modules, [$this, 'moduleSeed']);
                $this->info('All modules seeded.');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * @throws RuntimeException
     *
     * @return Repository
     */
    public function getModuleRepository()
    {
        $modules = $this->laravel['modules'];
        if (!$modules instanceof Repository) {
            throw new RuntimeException("Module repository not found!");
        }

        return $modules;
    }

    /**
     * @param $name
     *
     * @throws RuntimeException
     *
     * @return Module
     */
    public function getModuleByName($name)
    {
        $modules = $this->getModuleRepository();
        if ($modules->has($name) === false) {
            throw new RuntimeException("Module [$name] does not exists.");
        }

        return $modules->get($name);
    }

    /**
     * @param Module $module
     *
     * @return void
     */
    public function moduleSeed(Module $module)
    {
        $seeders = [];
        $name = $module->getName();
        $config = $module->get('migration');
        if (is_array($config) && array_key_exists('seeds', $config)) {
            foreach ((array)$config['seeds'] as $class) {
                if (@class_exists($class)) {
                    $seeders[] = $class;
                }
            }
        } else {
            $class = $this->getSeederName($name); //legacy support
            if (@class_exists($class)) {
                $seeders[] = $class;
            }
        }

        if (count($seeders) > 0) {
            array_walk($seeders, [$this, 'dbSeed']);
            $this->info("Module [$name] seeded.");
        }
    }

    /**
     * Seed the specified module.
     *
     * @param string $className
     *
     * @return array
     */
    protected function dbSeed($className)
    {
        $params = [
            '--class' => $className,
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
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
        );
    }
}
