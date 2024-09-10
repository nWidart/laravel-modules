<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

#[AsCommand('module:model-show', 'Show information about an Eloquent model in modules')]
class ModelShowCommand extends ShowModelCommand implements PromptsForMissingInput
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
     * @see \Illuminate\Console\GeneratorCommand
     */
    protected function qualifyModel(string $model): string
    {
        if (str_contains($model, '\\') && class_exists($model)) {
            return $model;
        }

        $modelPaths = $this->findModels($model);

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

    public function findModels(string $model): Collection
    {
        $pattern = sprintf(
            '%s/*/%s/%s.php',
            config('modules.paths.modules'),
            config('modules.paths.generator.model.path'),
            $model
        );

        return collect(File::glob($pattern))
            ->map($this->formatModuleNamespace(...));
    }

    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $selected_item = search(
            label: 'Select Model',
            options: function (string $search_value) {
                return $this->findModels(
                    Str::of($search_value)->wrap('', '*')
                )->toArray();
            },
            placeholder: 'type some thing',
            required: 'You must select one Model',
        );

        $input->setArgument(
            name: 'model',
            value: $selected_item
        );
    }
}
