<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Nwidart\Modules\Json;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends Command
{
    use InteractsWithModuleRepository;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the specified module by given package name (vendor/name).';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (is_null($this->argument('name'))) {
            return $this->installFromFile();
        }

        $installed = $this->install(
            $this->argument('name'),
            $this->argument('version'),
            $this->option('type'),
            $this->option('tree')
        );

        return $installed ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Install modules from modules.json file.
     */
    protected function installFromFile(): int
    {
        if (! file_exists($path = base_path('modules.json'))) {
            $this->error("File 'modules.json' does not exist in your project root.");

            return E_ERROR;
        }

        $modules = Json::make($path);

        // Each entry of the module.json "require" list holds the name, version and type of a module.
        /** @var array<int, array<string, mixed>> $dependencies */
        $dependencies = $modules->get('require', []);

        $failed = false;

        foreach ($dependencies as $module) {
            $module = collect($module);

            $installed = $this->install(
                $module->get('name'),
                $module->get('version'),
                $module->get('type')
            );

            $failed = $failed || ! $installed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Install the specified module.
     *
     * @param  string  $name
     * @param  string|null  $version  Null installs the Installer default.
     * @param  string|null  $type
     * @param  bool  $tree
     * @return bool Whether the installation succeeded.
     */
    protected function install($name, $version = null, $type = null, $tree = false): bool
    {
        $installer = new Installer(
            $name,
            $version,
            $type ?: $this->option('type'),
            $tree ?: $this->option('tree')
        );

        $installer->setRepository($this->moduleRepository());

        $installer->setConsole($this);

        if ($timeout = $this->option('timeout')) {
            $installer->setTimeout($timeout);
        }

        if ($path = $this->option('path')) {
            $installer->setPath($path);
        }

        try {
            $process = $installer->run();
        } catch (InvalidArgumentException $e) {
            $this->error("Failed to install module [{$name}]: {$e->getMessage()}");

            return false;
        }

        if (! $process->isSuccessful()) {
            $this->error("Failed to install module [{$name}] (exit code {$process->getExitCode()}).");

            return false;
        }

        if ($this->option('no-update')) {
            return true;
        }

        $module = $installer->getInstalledModuleName();
        $repository = $this->moduleRepository();

        // The repository caches its scan outside of tests: rescan to see the new module.
        if (method_exists($repository, 'resetModules')) {
            $repository->resetModules();
        }

        if ($repository->find($module) === null) {
            $this->error("Module [{$module}] was installed, but the modules repository cannot find it, so module:update was skipped. Make sure its folder is in a scanned modules path, then run: php artisan module:update {$module}");

            return false;
        }

        $code = $this->call('module:update', [
            'module' => $module,
        ]);

        if ($code !== self::SUCCESS) {
            $this->error("Module [{$module}] was installed, but module:update failed (exit code {$code}).");

            return false;
        }

        return true;
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of module will be installed.'],
            ['version', InputArgument::OPTIONAL, 'The version of module will be installed.'],
        ];
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
            ['timeout', null, InputOption::VALUE_OPTIONAL, 'The process timeout.', null],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The installation path.', null],
            ['type', null, InputOption::VALUE_OPTIONAL, 'The type of installation.', null],
            ['tree', null, InputOption::VALUE_NONE, 'Install the module as a git subtree', null],
            ['no-update', null, InputOption::VALUE_NONE, 'Disables the automatic update of the dependencies.', null],
        ];
    }
}
