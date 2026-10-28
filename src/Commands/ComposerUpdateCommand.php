<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Traits\PathNamespace;
use stdClass;

class ComposerUpdateCommand extends BaseCommand
{
    use PathNamespace;

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

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Updating Composer.json <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {

            $composer_path = $module->getPath().DIRECTORY_SEPARATOR.'composer.json';

            $contents = File::get($composer_path);

            // Decode as objects so empty objects ("aliases": {}) and key order survive the round trip.
            $composer = json_decode($contents);

            $autoload = $composer->autoload->{'psr-4'} ?? null;

            if (! $autoload instanceof stdClass) {
                return;
            }

            $module_namespace = $this->module_namespace($module->getStudlyName());
            $app_folder = (string) config('modules.paths.app_folder') ?: 'app/';

            // Legacy layout: the app folder is part of the namespace, e.g. "Modules\Blog\App\" => "app/".
            $key_name_with_app = collect([
                $module_namespace.'\\App\\',
                $module_namespace.'\\'.$this->path_namespace($app_folder).'\\',
            ])->first(fn (string $key) => property_exists($autoload, $key));

            if ($key_name_with_app === null) {
                return;
            }

            $key_name_with_out_app = $module_namespace.'\\';

            // The classes stay where they are: keep the folder of the legacy entry.
            $folder = $autoload->{$key_name_with_app} ?: $app_folder;

            // Replace the legacy entry in place, so the other entries keep their order.
            $updated = new stdClass;

            foreach (get_object_vars($autoload) as $key => $value) {
                if ($key === $key_name_with_app) {
                    $updated->{$key_name_with_out_app} = $folder;
                } elseif ($key !== $key_name_with_out_app) {
                    $updated->{$key} = $value;
                }
            }

            $composer->autoload->{'psr-4'} = $updated;

            // Same flags as composer; keep the trailing newline of the original file.
            $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            File::put($composer_path, $json.(str_ends_with($contents, "\n") ? "\n" : ''));

        });
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Updating Composer.json of modules...';
    }
}
