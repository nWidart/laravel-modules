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

    protected $signature = 'module:require {--dev} {packageName} {module?}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->argument('packageName')) {
            $this->error('packageName is required');
            return;
        }
        $this->getModule();
        $this->oldScripts = $this->getComposer(base_path('composer.json'))['scripts'];
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

    protected function getComposer($path)
    {
        return json_decode(file_get_contents($path), true);
    }

    private function runRequire()
    {
        $installer = new Installer($this->argument('packageName'), '');
        $installer->setConsole($this);
        if ($this->option('dev')) {
            $installer->setRequireDev(true);
        }
        $this->process = $installer->run();
    }

    private function updateModuleComposer()
    {
        $composer = $this->getComposer(base_path('composer.json'));
        $additionalScripts = $this->getAdditional($composer);
        $requireKey = $this->option('dev') ? 'require-dev' : 'require';
        $requireOtherKey = $this->option('dev') ? 'require' : 'require-dev';
        [$newPackage, $newPackageVersion] = $this->getNewPackageInfo($composer, $requireKey);
        $moduleComposerPath = $this->getModule()->getPath() . '/composer.json';
        $moduleComposer = $this->getComposer($moduleComposerPath);
        $moduleComposer = $this->setModuleComposer(
            $moduleComposer,
            $requireKey,
            $requireOtherKey,
            $newPackage,
            $newPackageVersion,
            $additionalScripts
        );
        $this->putComposer($moduleComposerPath, $moduleComposer);
    }

    protected function putComposer($path, $data)
    {
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * @param array $composer
     * @return array
     */
    private function getAdditional($composer)
    {
        $newScripts = $composer['scripts'];
        return array_diff_key($newScripts, $this->oldScripts);
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
     * @param string $requireKey
     * @param string $requireOtherKey
     * @param string $newPackage
     * @param string $newPackageVersion
     * @param array $additionalScripts
     * @return Json
     */
    private function setModuleComposer($moduleComposer, $requireKey, $requireOtherKey, $newPackage, $newPackageVersion, $additionalScripts)
    {
        $packageRequire = $moduleComposer[$requireKey] ?? [];
        $moduleComposer[$requireKey] = array_merge($packageRequire, [$newPackage => $newPackageVersion]);
        // check other require list
        unset($moduleComposer[$requireOtherKey][$newPackage]);
        if (empty($moduleComposer[$requireOtherKey])) {
            unset($moduleComposer[$requireOtherKey]);
        }
        $packageScripts = $moduleComposer['scripts'] ?? [];
        $packageScripts = array_merge($packageScripts, $additionalScripts);
        if (!empty($packageScripts)) {
            $moduleComposer['scripts'] = $packageScripts;
        }
        return $moduleComposer;
    }
}
