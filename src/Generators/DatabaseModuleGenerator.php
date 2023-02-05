<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Laravel\LaravelDatabaseRepository;
use Nwidart\Modules\Support\Config\GenerateConfigReader;

/**
 * Class DatabaseModuleGenerator.
 * @package Nwidart\Modules\Generators
 * @property LaravelDatabaseRepository $module
 */
class DatabaseModuleGenerator extends ModuleGenerator
{
    protected $silentOutput = false;

    public function setSilentOutput($bool = true)
    {
        $this->silentOutput = $bool;

        return $this;
    }

    /**
     * Generate the module.
     */
    public function generate(): int
    {
        return DB::transaction(function () {
            $name = $this->getName();

            if ($this->module->has($name)) {
                if ($this->force) {
                    $this->module->delete($name);
                } else {
                    if (!$this->silentOutput) {
                        $this->console->info("Module [{$name}] already exist!");
                    } else {
                        abort(400, "Module [{$name}] already exist!");
                    }

                    return false;
                }
            }

            // Get data from module.json.
            $data = $this->getStubContents('json');
            $data = json_decode($data, true);
            $data['path'] = $this->module->getModulePath($this->getName());
            if ($this->type === 'plain') {
                $data['provider'] = [];
            }
            $this->module->getModel()->create($data);

            $this->generateFolders();

            $this->generateModuleJsonFile();

            if ($this->type !== 'plain') {
                $this->generateFiles();
                $this->generateResources();
            }

            // Re-check if we created successfully.
            $success = $this->module->has($name);
            if ($success) {
                if (!$this->silentOutput) {
                    $this->console->info("Module [{$name}] created successfully.");
                }
            }

            return $success;
        });
    }

    /**
     * Generate the files.
     */
    public function generateFiles()
    {
        foreach ($this->getFiles() as $stub => $file) {
            $path = $this->module->getModulePath($this->getName()) . $file;

            if (!$this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
            }

            $this->filesystem->put($path, $this->getStubContents($stub));

            if (!$this->silentOutput) {
                $this->console->info("Created : {$path}");
            }
        }
    }

    /**
     * Generate some resources.
     */
    public function generateResources()
    {
        $moduleName = $this->getName();

        if (GenerateConfigReader::read('seeder')->generate() === true) {
            Artisan::call('module:make-seed', [
                'name'     => $moduleName,
                'module'   => $moduleName,
                '--master' => true,
            ]);
        }

        if (GenerateConfigReader::read('provider')->generate() === true) {
            Artisan::call('module:make-provider', [
                'name'     => $moduleName . 'ServiceProvider',
                'module'   => $moduleName,
                '--master' => true,
            ]);
            Artisan::call('module:route-provider', [
                'module' => $moduleName,
            ]);
        }

        if (GenerateConfigReader::read('controller')->generate() === true) {
            $options = $this->type == 'api' ? ['--api' => true] : [];
            Artisan::call('module:make-controller', [
                    'controller' => $moduleName . 'Controller',
                    'module'     => $moduleName,
                ] + $options);
        }
    }

    /**
     * Generate the module.json file
     */
    private function generateModuleJsonFile()
    {
        $path = $this->module->getModulePath($this->getName()) . 'module.json';

        if (!$this->filesystem->isDirectory($dir = dirname($path))) {
            $this->filesystem->makeDirectory($dir, 0775, true);
        }

        $this->filesystem->put($path, $this->getStubContents('json'));

        if (!$this->silentOutput) {
            $this->console->info("Created : {$path}");
        }
    }
}
