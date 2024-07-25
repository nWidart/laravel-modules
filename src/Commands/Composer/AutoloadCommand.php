<?php

namespace Nwidart\Modules\Commands\Composer;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Commands\BaseCommand;
use Illuminate\Support\Str;

class AutoloadCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:composer-autoload
        {--p|path= : Custom app path.}';

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
            if (!$composer) {
                return false;
            }

            // Get autoload.psr-4
            $autoload_psr4 = data_get($composer, 'autoload.psr-4') ?? [];
            $autoload_dev_psr4 = data_get($composer, 'autoload-dev.psr-4') ?? [];

            // Get the module name.
            $name = $module->getStudlyName();

            $path = trim($this->option('path') ?? (strlen($p = config('modules.paths.app_folder')) ? $p : 'app'), '/') . '/';

            $path_name = trim($path, '/');

            // Remove old app key
            $autoload_old_app_key = sprintf('Modules\\%s\\', $name);
            if (array_key_exists($autoload_old_app_key, $autoload_psr4)) {
                unset($autoload_psr4[$autoload_old_app_key]);
            }

            /*
             * ---------------------
             * Update autoload.psr-4
             * ---------------------
             */
            // Set the keys
            $psr4_app = strlen($path_name) ? sprintf('Modules\\%s\\%s\\', $name, Str::studly($path_name)) : sprintf('Modules\\%s\\', $name);
            $psr4_factories = sprintf('Modules\\%s\\Database\\Factories\\', $name);
            $psr4_seeders = sprintf('Modules\\%s\\Database\\Seeders\\', $name);

            // Update the values
            $autoload_psr4[$psr4_app] = Str::lower($path ?? 'app/');
            $autoload_psr4[$psr4_factories] = 'database/factories/';
            $autoload_psr4[$psr4_seeders] = 'database/seeders/';

            // Update composer.json
            data_set($composer, 'autoload.psr-4', $autoload_psr4);

            /*
             * -------------------------
             * Update autoload-dev.psr-4
             * -------------------------
             */
            // Set the keys
            $dev_psr4_test = sprintf('Modules\\%s\\Tests\\', $name);

            // Update the values
            $autoload_dev_psr4[$dev_psr4_test] = 'tests/';

            // Update composer.json
            data_set($composer, 'autoload-dev.psr-4', $autoload_dev_psr4);

            /*
             * Save composer.json
             */
            file_put_contents($composer_file, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        });
    }

    public function getInfo(): string
    {
        return 'Updating modules composer.json autoloads ...';
    }
}
