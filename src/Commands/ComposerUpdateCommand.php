<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Facades\File;

class ComposerUpdateCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:composer-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update autoload of composer.json file';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Updating Composer.json <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {

            $composer_path = $module->getPath() . DIRECTORY_SEPARATOR . 'composer.json';

            $composer = json_decode(File::get($composer_path), true);

            $autoload = data_get($composer, 'autoload.psr-4');

            if (! $autoload) {
                return;
            }

            $key_name_with_app = sprintf('Modules\\%s\\App\\', $module->getStudlyName());

            if (! array_key_exists($key_name_with_app, $autoload)) {
                return;
            }

            unset($autoload[$key_name_with_app]);
            $key_name_with_out_app            = sprintf('Modules\\%s\\', $module->getStudlyName());
            $autoload[$key_name_with_out_app] = 'app/';

            data_set($composer, 'autoload.psr-4', $autoload);

            file_put_contents($composer_path, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        });
    }

    public function getInfo(): string|null
    {
        return 'Updating Composer.json of modules...';
    }
}
