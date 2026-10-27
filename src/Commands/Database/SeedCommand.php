<?php

namespace Nwidart\Modules\Commands\Database;

use ErrorException;
use Illuminate\Console\View\TaskResult;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SeedCommand extends BaseCommand
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
     * Whether a db:seed call failed for the module being seeded.
     */
    private bool $seederFailed = false;

    /**
     * Warnings about missing seeders, printed after the task line so they do not split it.
     *
     * @var array<int, string>
     */
    private array $warnings = [];

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $this->warnings = [];

        $this->components->task("Seeding <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $this->seederFailed = false;

            try {
                $this->moduleSeed($module);
            } catch (\Error $e) {
                $e = new ErrorException($e->getMessage(), $e->getCode(), 1, $e->getFile(), $e->getLine(), $e);
                $this->reportException($e);
                $this->renderException($this->getOutput(), $e);
                $this->markAsFailed();

                return TaskResult::Failure->value;
            } catch (\Exception $e) {
                $this->reportException($e);
                $this->renderException($this->getOutput(), $e);
                $this->markAsFailed();

                return TaskResult::Failure->value;
            }

            return $this->seederFailed ? TaskResult::Failure->value : TaskResult::Success->value;
        });

        foreach ($this->warnings as $warning) {
            $this->components->warn($warning);
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Seeding module ...';
    }

    /**
     * @throws RuntimeException
     */
    public function getModuleRepository(): RepositoryInterface
    {
        // Read untyped on purpose: the binding may be anything, so the guard below is a real check.
        $modules = $this->laravel['modules'];
        if (! $modules instanceof RepositoryInterface) {
            throw new RuntimeException('Module repository not found!');
        }

        return $modules;
    }

    /**
     * @param  string  $name
     * @return Module
     *
     * @throws RuntimeException
     */
    public function getModuleByName($name)
    {
        // find() is declared on RepositoryInterface; has() is not, so custom repositories may lack it.
        $module = $this->getModuleRepository()->find((string) $name);

        if ($module === null) {
            throw new RuntimeException("Module [$name] does not exists.");
        }

        return $module;
    }

    /**
     * @return void
     */
    public function moduleSeed(Module $module)
    {
        $seeders = [];
        $name = $module->getName();
        $config = $module->get('migration');

        if (is_array($config) && array_key_exists('seeds', $config)) {
            foreach ((array) $config['seeds'] as $class) {
                if (class_exists($class)) {
                    $seeders[] = $class;
                } else {
                    $this->warnings[] = "Module [$name] seeder [$class] not found, skipped.";
                }
            }
        } else {
            $class = $this->getSeederName($name); // legacy support

            $seederName = implode('\\', array_map('ucwords', explode('\\', $class)));

            if (class_exists($seederName)) {
                $seeders[] = $seederName;
            } else {
                // look at other namespaces
                $classes = $this->getSeederNames($name);
                foreach ($classes as $class) {
                    if (class_exists($class)) {
                        $seeders[] = $class;
                    }
                }

                if (count($seeders) === 0) {
                    $this->warnings[] = "Module [$name] has no seeder: class [$seederName] not found.";
                }
            }
        }

        if (count($seeders) > 0) {
            array_walk($seeders, $this->dbSeed(...));
            $this->info("Module [$name] seeded.");
        }
    }

    /**
     * Seed the specified module.
     *
     * @param  string  $className
     * @return void
     */
    protected function dbSeed($className)
    {
        if ($option = $this->option('class')) {
            $params['--class'] = Str::finish(substr($className, 0, strrpos($className, '\\')), '\\').$option;
        } else {
            $params = ['--class' => $className];
        }

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        if ($option = $this->option('force')) {
            $params['--force'] = $option;
        }

        if ($this->call('db:seed', $params) !== self::SUCCESS) {
            $this->seederFailed = true;
            $this->markAsFailed();
        }
    }

    /**
     * Get master database seeder name for the specified module.
     *
     * @param  string  $name
     * @return string
     */
    public function getSeederName($name)
    {
        $name = Str::studly($name);

        $namespace = $this->moduleRepository()->config('namespace');
        $config = GenerateConfigReader::read('seeder');
        $seederPath = str_replace('/', '\\', $config->getPath());

        return $namespace.'\\'.$name.'\\'.$seederPath.'\\'.$name.'DatabaseSeeder';
    }

    /**
     * Get master database seeder name for the specified module under a different namespace than Modules.
     *
     * @param  string  $name
     * @return array<int, string> The seeder class names, one per scanned modules path.
     */
    public function getSeederNames($name)
    {
        $name = Str::studly($name);

        $seederPath = GenerateConfigReader::read('seeder');
        $seederPath = str_replace('/', '\\', $seederPath->getPath());

        $foundModules = [];
        foreach ($this->moduleRepository()->config('scan.paths') as $path) {
            $namespace = array_slice(explode('/', $path), -1)[0];
            $foundModules[] = $namespace.'\\'.$name.'\\'.$seederPath.'\\'.$name.'DatabaseSeeder';
        }

        return $foundModules;
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  OutputInterface  $output
     * @return void
     */
    protected function renderException($output, \Exception $e)
    {
        $this->laravel->make(ExceptionHandler::class)->renderForConsole($output, $e);
    }

    /**
     * Report the exception to the exception handler.
     *
     * @return void
     */
    protected function reportException(\Exception $e)
    {
        $this->laravel->make(ExceptionHandler::class)->report($e);
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder.'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
