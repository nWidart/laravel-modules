<?php

namespace Nwidart\Modules\Commands\Composer;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Commands\BaseCommand;
use Illuminate\Support\Str;

class AutoloadCommand extends BaseCommand
{
    /**
     * The console command name.
     */
    protected $name = 'module:composer-autoload';

    /**
     * The console command description.
     */
    protected $description = 'Update composer.json autoloads';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Updating <fg=cyan;options=bold>{$module->getName()}</>", function () use ($module) {
            $composer_file = $module->getPath() . DIRECTORY_SEPARATOR . 'composer.json';
            $composer = json_decode(File::get($composer_file), true);

            $autoload_psr4 = data_get($composer, 'autoload.psr-4') ?? [];
            $autoload_dev_psr4 = data_get($composer, 'autoload-dev.psr-4') ?? [];

            // Get the module name.
            $name = $module->getStudlyName();
            $app_path = trim(strlen($path = config('modules.paths.app_folder')) ? $path : 'app', '/') . '/'; // accept app_path option
            $app_path_name = trim($app_path, '/');

            // Remove old app key
            $autoload_old_app_key = sprintf('Modules\\%s\\', $name);
            if (array_key_exists($autoload_old_app_key, $autoload_psr4)) {
                unset($autoload_psr4[$autoload_old_app_key]);
            }

            // Update autoload.psr-4
            if (strlen($app_path_name)) {
                $autoload_app = sprintf('Modules\\%s\\%s\\', $name, Str::studly($app_path_name));
            } else {
                $autoload_app = sprintf('Modules\\%s\\', $name);
            }
            $autoload_factories = sprintf('Modules\\%s\\Database\\Factories\\', $name);
            $autoload_seeders = sprintf('Modules\\%s\\Database\\Seeders\\', $name);
            $autoload_psr4 += [
                $autoload_app => Str::lower($app_path ?? '/'),
                $autoload_factories => 'database/factories/',
                $autoload_seeders => 'database/seeders/',
            ];
            data_set($composer, 'autoload.psr-4', $autoload_psr4);

            // Update autoload-dev.psr-4
            $autoload_dev_psr4_test = sprintf('Modules\\%s\\Tests\\', $name);
            $autoload_dev_psr4 += [
                $autoload_dev_psr4_test => 'tests/',
            ];
            data_set($composer, 'autoload-dev.psr-4', $autoload_dev_psr4);

            file_put_contents($composer_file, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        });
    }

    public function getInfo(): string
    {
        return 'Updating modules composer.json autoloads ...';
    }
}
