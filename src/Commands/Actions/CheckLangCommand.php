<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Module;

/**
 * @phpstan-type LangDirectory array{name: string, module: string, path: string, files: array<string>}
 */
class CheckLangCommand extends BaseCommand
{
    /**
     * The lang path inside a module, with a leading directory separator.
     */
    private readonly string $langPath;

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

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $directories = $this->getDirectories($module);

        if (! $directories) {
            return;
        }

        // Run both checks, so every missing file and key is reported, not only the first kind.
        $missingFiles = $this->checkMissingFiles($directories);
        $missingKeys = $this->checkMissingKeys($directories);

        if ($missingFiles || $missingKeys) {
            $this->markAsFailed();
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Checking languages ...';
    }

    /**
     * Get the locale directories of the module, or false when there are fewer than two.
     *
     * @param  Module  $module
     * @return Collection<int, LangDirectory>|false
     */
    private function getDirectories($module)
    {
        $moduleName = $module->getStudlyName();
        $path = $module->getPath().$this->langPath;
        $directories = [];
        if (is_dir($path)) {
            // Filesystem::directories() is typed as a plain array; it holds the directory paths.
            /** @var array<int, string> $directories */
            $directories = $this->laravel->make(Filesystem::class)->directories($path);
            $directories = array_map(fn ($directory) => [
                'name' => basename($directory),
                'module' => $moduleName,
                'path' => $directory,
                'files' => array_map(fn ($file) => basename($file), File::glob($directory.DIRECTORY_SEPARATOR.'*')),
            ], $directories);
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

    /**
     * Report the language files missing from a locale. Returns whether any file is missing.
     *
     * @param  Collection<int, LangDirectory>  $directories
     */
    private function checkMissingFiles(Collection $directories): bool
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

        return count($missingFilesMessage) > 0;
    }

    /**
     * Report the language keys missing from a locale. Returns whether any key is missing.
     *
     * @param  Collection<int, LangDirectory>  $directories
     */
    private function checkMissingKeys(Collection $directories): bool
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

                        // Swap only the locale directory: the locale can also appear elsewhere in the path (en in /home/ben/).
                        $basePath = dirname($directory['path']).DIRECTORY_SEPARATOR.$langDirectory;

                        $otherLangKeys = $this->getLangKeys($basePath.DIRECTORY_SEPARATOR.$file);

                        if ($otherLangKeys == false) {
                            // The file is missing in this locale; checkMissingFiles() already reported it.
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

        return count($missingKeysMessage) > 0;
    }

    /**
     * Get the dotted keys of a language file, or false when the file does not exist.
     *
     * @param  string  $file
     * @return Collection<int, array-key>|false
     */
    private function getLangKeys($file)
    {
        if (File::exists($file)) {
            $lang = File::getRequire($file);

            return collect(Arr::dot($lang))->keys();
        } else {
            return false;
        }
    }
}
