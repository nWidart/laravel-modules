<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Repository;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Debug\Exception\FatalThrowableError;

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
     * @throws FatalThrowableError
     */
    public function handle()
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
            $e = new FatalThrowableError($e);

            $this->reportException($e);

            $this->renderException($this->getOutput(), $e);

            return 1;
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
                if (class_exists($class)) {
                    $seeders[] = $class;
                }
            }
        } else {
            $class = $this->getSeederName($name); //legacy support
            if (class_exists($class)) {
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
        $seederPath = GenerateConfigReader::read('seeder');
        $seederPath = str_replace('/', '\\', $seederPath->getPath());

        return $namespace . '\\' . $name . '\\' . $seederPath . '\\' . $name . 'DatabaseSeeder';
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    protected function renderException($output, \Exception $e)
    {
        $this->laravel[ExceptionHandler::class]->renderForConsole($output, $e);
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function reportException(\Exception $e)
    {
        $this->laravel[ExceptionHandler::class]->report($e);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
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
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
