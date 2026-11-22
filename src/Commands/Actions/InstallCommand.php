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

        // Each entry of the modules.json "require" list holds the name, version, type and optional path of a module.
        $dependencies = $modules->get('require', []);

        if (! is_array($dependencies)) {
            $this->error('The "require" key of modules.json must be a list of entries such as {"name": "vendor/module"}.');

            return self::FAILURE;
        }

        // --path is the folder of one module: applied to several, every module would go into the same folder.
        if ($this->option('path') && count($dependencies) > 1) {
            $this->error('The --path option sets the folder of a single module, but modules.json lists '.count($dependencies).' modules. Remove --path and set a "path" on each entry of modules.json instead.');

            return self::FAILURE;
        }

        $failed = false;
        $position = 0;

        foreach ($dependencies as $module) {
            $position++;

            if (($problem = $this->invalidEntryProblem($module)) !== null) {
                $this->error("Skipping entry #{$position} of the modules.json \"require\" list: {$problem}");
                $failed = true;

                continue;
            }

            /** @var array<string, mixed> $module Checked by invalidEntryProblem(). */
            $path = $module['path'] ?? null;

            $installed = $this->install(
                $module['name'],
                $module['version'] ?? null,
                $module['type'] ?? null,
                path: is_string($path) && $path !== '' ? $path : null
            );

            $failed = $failed || ! $installed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get why a modules.json "require" entry cannot be installed, null when it can.
     */
    protected function invalidEntryProblem(mixed $module): ?string
    {
        if (! is_array($module) || ($module !== [] && array_is_list($module))) {
            return 'expected an object such as {"name": "vendor/module"}, got '.(is_array($module) ? 'a list' : get_debug_type($module)).'.';
        }

        if (! is_string($module['name'] ?? null) || $module['name'] === '') {
            return '"name" must be a package name such as "vendor/module".';
        }

        foreach (['version', 'type'] as $key) {
            if (isset($module[$key]) && ! is_scalar($module[$key])) {
                return "\"{$key}\" of [{$module['name']}] must be a string.";
            }
        }

        return null;
    }

    /**
     * Install the specified module.
     *
     * @param  string  $name
     * @param  string|null  $version  Null installs the Installer default.
     * @param  string|null  $type
     * @param  bool  $tree
     * @param  string|null  $path  The destination folder, null for the --path option or else the repository module path.
     * @return bool Whether the installation succeeded.
     */
    protected function install($name, $version = null, $type = null, $tree = false, ?string $path = null): bool
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

        if ($path = $path ?: $this->option('path')) {
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
