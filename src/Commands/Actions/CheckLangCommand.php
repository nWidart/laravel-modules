<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Module;
use Nwidart\Modules\Traits\WritesJsonOutput;
use Symfony\Component\Console\Input\InputOption;

/**
 * @phpstan-type LangDirectory array{name: string, module: string, path: string, files: array<string>}
 * @phpstan-type MissingFile array{locale: string, file: string}
 * @phpstan-type MissingKey array{locale: string, file: string, key: string}
 * @phpstan-type Findings array{missing_files: list<MissingFile>, missing_keys: list<MissingKey>}
 */
class CheckLangCommand extends BaseCommand
{
    use WritesJsonOutput;

    /**
     * The lang path inside a module, with a leading directory separator.
     */
    private readonly string $langPath;

    /**
     * The findings of each checked module, in the order the modules ran, for --json.
     *
     * @var list<array{name: string, checked: bool, locales: object}>
     */
    private array $modules = [];

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

    #[\Override]
    public function handle()
    {
        $this->enterJsonMode();
        $this->modules = [];

        $code = parent::handle();

        if ($this->inJsonMode()) {
            $this->writeJson(['modules' => $this->modules]);
        }

        return $code;
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $moduleName = $module->getStudlyName();

        $directories = $this->getDirectories($module);

        $checked = $directories->count() >= 2;

        if ($directories->isEmpty()) {
            $this->components->info("No language files found in module $moduleName");
        } elseif (! $checked) {
            $this->components->warn("Only one language file found in module $moduleName");
        }

        $findings = $checked
            ? $this->findMissing($directories)
            : ['missing_files' => [], 'missing_keys' => []];

        if ($this->inJsonMode()) {
            $this->modules[] = [
                'name' => $module->getName(),
                'checked' => $checked,
                'locales' => $this->localesDocument($directories->pluck('name')->all(), $findings),
            ];
        } elseif ($checked) {
            $this->reportMissingFiles($moduleName, $findings['missing_files']);
            $this->reportMissingKeys($moduleName, $findings['missing_keys']);
        }

        if ($findings['missing_files'] || $findings['missing_keys']) {
            $this->markAsFailed();
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Checking languages ...';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['json', null, InputOption::VALUE_NONE, 'Print the missing files and keys as one JSON document'],
        ];
    }

    /**
     * Get the locale directories of the module.
     *
     * @return Collection<int, LangDirectory>
     */
    private function getDirectories(Module $module): Collection
    {
        $path = $module->getPath().$this->langPath;

        if (! is_dir($path)) {
            return collect();
        }

        // Filesystem::directories() is typed as a plain array; it holds the directory paths.
        /** @var array<int, string> $directories */
        $directories = $this->laravel->make(Filesystem::class)->directories($path);

        return collect($directories)->map(fn ($directory) => [
            'name' => basename($directory),
            'module' => $module->getStudlyName(),
            'path' => $directory,
            'files' => array_map(fn ($file) => basename($file), File::glob($directory.DIRECTORY_SEPARATOR.'*')),
        ]);
    }

    /**
     * Find the language files and keys missing from each locale, in the order they are found.
     *
     * A file is missing from a locale when another locale has it. A key is missing from a
     * locale when another locale has it in the same file, and the locale has that file.
     *
     * @param  Collection<int, LangDirectory>  $directories
     * @return Findings
     */
    private function findMissing(Collection $directories): array
    {
        $uniqueLangFiles = $directories->pluck('files')->flatten()->unique()->values();

        $missingFiles = [];
        foreach ($directories as $directory) {
            foreach ($uniqueLangFiles->diff($directory['files']) as $file) {
                $missingFiles[] = ['locale' => $directory['name'], 'file' => (string) $file];
            }
        }

        // The dotted keys of each locale file, or false when the locale has no such file.
        $keys = [];
        foreach ($directories as $directory) {
            foreach ($uniqueLangFiles as $file) {
                $keys[$directory['name']][$file] = $this->getLangKeys($directory['path'].DIRECTORY_SEPARATOR.$file);
            }
        }

        $missingKeys = [];
        foreach ($directories as $directory) {
            foreach ($uniqueLangFiles as $file) {
                $langKeys = $keys[$directory['name']][$file];

                if ($langKeys === false) {
                    continue;
                }

                foreach ($directories as $other) {
                    $otherLangKeys = $keys[$other['name']][$file];

                    // A missing file is reported as such, not as missing keys.
                    if ($other['name'] === $directory['name'] || $otherLangKeys === false) {
                        continue;
                    }

                    foreach ($langKeys->diff($otherLangKeys) as $key) {
                        $missingKeys["{$other['name']}\0{$file}\0{$key}"] = [
                            'locale' => $other['name'],
                            'file' => (string) $file,
                            'key' => (string) $key,
                        ];
                    }
                }
            }
        }

        return ['missing_files' => $missingFiles, 'missing_keys' => array_values($missingKeys)];
    }

    /**
     * Print the language files missing from each locale.
     *
     * @param  list<MissingFile>  $missingFiles
     */
    private function reportMissingFiles(string $moduleName, array $missingFiles): void
    {
        collect($missingFiles)->groupBy('locale')->each(function (Collection $missing, $locale) use ($moduleName) {
            $this->components->error("Missing language files in $locale directory");

            $this->components->bulletList(
                $missing->map(fn ($file) => " $moduleName - Missing language file: {$file['locale']}/{$file['file']}")->all()
            );

            $this->newLine();
        });
    }

    /**
     * Print the language keys missing from each locale.
     *
     * @param  list<MissingKey>  $missingKeys
     */
    private function reportMissingKeys(string $moduleName, array $missingKeys): void
    {
        collect($missingKeys)->groupBy('locale')->each(function (Collection $missing, $locale) use ($moduleName) {
            $this->components->error("Missing language keys for directory $locale:");

            $this->components->bulletList(
                $missing->map(fn ($key) => " $moduleName - Missing language key: {$key['locale']}/{$key['file']} | key: {$key['key']}")->all()
            );

            $this->newLine();
        });
    }

    /**
     * Build the --json locales object: each locale, sorted, with the files and keys missing from it.
     *
     * Maps are objects, so an empty one encodes as {} rather than [].
     *
     * @param  array<int, string>  $locales
     * @param  Findings  $findings
     */
    private function localesDocument(array $locales, array $findings): object
    {
        $document = [];

        sort($locales, SORT_STRING);

        foreach ($locales as $locale) {
            $missingKeys = [];

            foreach ($findings['missing_keys'] as $missing) {
                if ($missing['locale'] === $locale) {
                    $missingKeys[$missing['file']][] = $missing['key'];
                }
            }

            $document[$locale] = [
                'missing_files' => array_values(array_map(
                    fn ($missing) => $missing['file'],
                    array_filter($findings['missing_files'], fn ($missing) => $missing['locale'] === $locale),
                )),
                'missing_keys' => (object) $missingKeys,
            ];
        }

        return (object) $document;
    }

    /**
     * Get the dotted keys of a language file, or false when the file does not exist.
     *
     * @return Collection<int, array-key>|false
     */
    private function getLangKeys(string $file): Collection|false
    {
        if (! File::exists($file)) {
            return false;
        }

        return collect(Arr::dot(File::getRequire($file)))->keys();
    }
}
