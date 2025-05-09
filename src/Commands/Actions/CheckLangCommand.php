<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Support\Collection;
use Nwidart\Modules\Commands\BaseCommand;

class CheckLangCommand extends BaseCommand
{
    private $langPath;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:lang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check missing language keys in the specified module.';

    public function __construct()
    {
        parent::__construct();

        $this->langPath = DIRECTORY_SEPARATOR.config('modules.paths.generator.lang.path', 'Resources/lang');
    }

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $directories = $this->getDirectories($module);

        if (! $directories) {
            return;
        }

        $this->checkMissingFiles($directories);

        $this->checkMissingKeys($directories);
    }

    public function getInfo(): ?string
    {
        return 'Checking languages ...';
    }

    private function getLangFiles($module)
    {
        $files = [];
        $path = $module->path($this->langPath);
        if (is_dir($path)) {
            $files = array_merge($files, $this->laravel['files']->all($path));
        }

        return $files;
    }

    private function getDirectories($module)
    {
        $moduleName = $module->getStudlyName();
        $path = $module->path($this->langPath);
        $directories = [];
        if (is_dir($path)) {
            $directories = $this->laravel['files']->directories($path);
            $directories = array_map(function ($directory) use ($moduleName) {
                return [
                    'name' => basename($directory),
                    'module' => $moduleName,
                    'path' => $directory,
                    'files' => array_map(function ($file) {
                        return basename($file);
                    }, \File::glob($directory.DIRECTORY_SEPARATOR.'*')),
                ];
            }, $directories);
        }

        if (count($directories) == 0) {
            $this->components->info("No language files found in module $moduleName");

            return false;
        }

        if (count($directories) == 1) {
            $this->components->warn("Only one language file found in module $moduleName");

            return false;
        }

        return collect($directories);
    }

    private function checkMissingFiles(Collection $directories)
    {
        // show missing files
        $missingFilesMessage = [];

        $uniqeLangFiles = $directories->pluck('files')->flatten()->unique()->values();

        $directories->each(function ($directory) use ($uniqeLangFiles, &$missingFilesMessage) {

            $missingFiles = $uniqeLangFiles->diff($directory['files']);

            if ($missingFiles->count() > 0) {
                $missingFiles->each(function ($missingFile) use ($directory, &$missingFilesMessage) {
                    $missingFilesMessage[$directory['name']][] = " {$directory['module']} - Missing language file: {$directory['name']}/{$missingFile}";
                });
            }
        });

        if (count($missingFilesMessage) > 0) {

            collect($missingFilesMessage)->each(function ($messages, $langDirectory) {

                $this->components->error("Missing language files in $langDirectory directory");

                $this->components->bulletList(
                    collect($messages)->unique()->values()->toArray()
                );

                $this->newLine();
            });
        }
    }

    private function checkMissingKeys(Collection $directories)
    {
        // show missing keys
        $uniqeLangFiles = $directories->pluck('files')->flatten()->unique();
        $langDirectories = $directories->pluck('name');

        $missingKeysMessage = [];
        $directories->each(function ($directory) use ($uniqeLangFiles, $langDirectories, &$missingKeysMessage) {

            $uniqeLangFiles->each(function ($file) use ($directory, $langDirectories, &$missingKeysMessage) {
                $langKeys = $this->getLangKeys($directory['path'].DIRECTORY_SEPARATOR.$file);

                if ($langKeys == false) {
                    return;
                }

                $langDirectories->each(function ($langDirectory) use ($directory, $file, $langKeys, &$missingKeysMessage) {

                    if ($directory['name'] != $langDirectory) {

                        $basePath = str_replace($directory['name'], $langDirectory, $directory['path']);

                        $otherLangKeys = $this->getLangKeys($basePath.DIRECTORY_SEPARATOR.$file);

                        if ($otherLangKeys == false) {
                            return;
                        }

                        $missingKeys = $langKeys->diff($otherLangKeys);
                        if ($missingKeys->count() > 0) {

                            $missingKeys->each(function ($missingKey) use ($directory, $langDirectory, $file, &$missingKeysMessage) {
                                $missingKeysMessage[$langDirectory][] = " {$directory['module']} - Missing language key: {$langDirectory}/{$file} | key: $missingKey";
                            });
                        }
                    }
                });
            });
        });

        if (count($missingKeysMessage) > 0) {

            collect($missingKeysMessage)->each(function ($messages, $langDirectory) {

                $this->components->error("Missing language keys for directory $langDirectory:");

                $this->components->bulletList(
                    collect($messages)->unique()->values()->toArray()
                );

                $this->newLine();
            });
        }
    }

    private function getLangKeys($file)
    {
        if (\File::exists($file)) {
            $lang = \File::getRequire($file);

            return collect(\Arr::dot($lang))->keys();
        } else {
            return false;
        }
    }
}
