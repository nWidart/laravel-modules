<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

#[AsCommand('module:model-show', 'Show information about an Eloquent model in modules')]
class ModelShowCommand extends ShowModelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:model-show';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about an Eloquent model in modules';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:model-show {model : The model to show}
                {--database= : The database connection to use}
                {--json : Output the model as JSON}';

    /**
     * Qualify the given model class base name.
     *
     *
     * @see \Illuminate\Console\GeneratorCommand
     */
    protected function qualifyModel(string $model): string
    {
        if (str_contains($model, '\\') && class_exists($model)) {
            return $model;
        }

        $pattern = sprintf(
            '%s/*/%s/%s.php',
            config('modules.paths.modules'),
            config('modules.paths.generator.model.path'),
            $model
        );

        $modelPaths = collect(File::glob($pattern))
            ->map($this->formatModuleNamespace(...));

        if ($modelPaths->count() == 0) {
            return $model;
        } elseif ($modelPaths->count() == 1) {
            return $this->formatModuleNamespace($modelPaths->first());
        }

        return select(
            label: 'Select Model',
            options: $modelPaths,
            required: 'You must select at least one model',
        );
    }

    private function formatModuleNamespace(string $path): string
    {
        return
            Str::of($path)
                ->after(base_path().DIRECTORY_SEPARATOR)
                ->replace(
                    [config('modules.paths.app_folder'), '/', '.php'],
                    ['', '\\', ''],
                )->toString();
    }
}
