<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Json;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RequireCommand extends Command
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:require';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add dependencies for the specified module.';
    /**
     * @var array
     */
    protected $oldScripts = null;

    /**
     * @var Process
     */
    protected $process = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->oldScripts = $this->getRootComposer()['scripts'];
        $this->info('installing package...');
        $this->runRequire();
        if ($this->process->isSuccessful()) {
            $this->info('package install success');
            $this->info('updating module composer....');
            $this->updateModuleComposer();
            $this->info('updated module composer');
        } else {
            $this->warn('package install failed');
        }
    }

    protected function getRootComposer()
    {
        return json_decode(file_get_contents(base_path('composer.json')), true);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['packageName', InputArgument::REQUIRED, 'The package name will be installed.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be updated.'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['dev', 'd', InputOption::VALUE_OPTIONAL, 'If install package to require-dev'],
        ];
    }

    private function runRequire()
    {
        $installer = new Installer($this->argument('packageName'));
        if ($this->option('dev')) {
            $installer->setRequireDev(true);
        }
        $this->process = $installer->run();
    }

    private function updateModuleComposer()
    {
        $composer = $this->getRootComposer();
        $additionalScripts = $this->getAdditional($composer);
        $require_key = $this->option('dev') ? 'require-dev' : 'require';
        [$newPackage, $newPackageVersion] = $this->getNewPackageInfo($composer, $require_key);
        $moduleComposer = $this->getModule()->json('composer.json');
        $this->setComposer($moduleComposer, $require_key, $newPackage, $newPackageVersion, $additionalScripts);
        $moduleComposer->save();
    }

    /**
     * @param array $composer
     * @return array
     */
    private function getAdditional($composer)
    {
        $newScripts = $composer['scripts'];
        return array_diff($newScripts, $this->oldScripts);
    }

    /**
     * @param array $composer
     * @param string $require_key
     * @return array
     */
    private function getNewPackageInfo($composer, $require_key)
    {
        $newPackage = $this->argument('packageName');
        $newPackageVersion = $composer[$require_key][$this->argument('packageName')];

        return [$newPackage, $newPackageVersion];
    }

    /**
     * @param Json $moduleComposer
     * @param string $require_key
     * @param string $newPackage
     * @param string $newPackageVersion
     * @param array $additionalScripts
     */
    private function setComposer($moduleComposer, $require_key, $newPackage, $newPackageVersion, $additionalScripts)
    {
        $packageRequire = $moduleComposer->get($require_key) ?? [];
        $packageRequire = array_merge($packageRequire, [$newPackage => $newPackageVersion]);
        $moduleComposer->set($require_key, $packageRequire);
        $packageScripts = $moduleComposer->get('scripts') ?? [];
        $packageScripts = array_merge($packageScripts, $additionalScripts);
        $moduleComposer->set('scripts', $packageScripts);
    }
}
