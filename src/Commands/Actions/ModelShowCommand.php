<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Eloquent\ModelInspector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Traits\PathNamespace;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

#[AsCommand('module:model-show', 'Show information about an Eloquent model in modules')]
class ModelShowCommand extends ShowModelCommand implements PromptsForMissingInput
{
    use PathNamespace;

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
     * Get the class name of a model file found in the modules path.
     *
     * The module folder gives the module namespace (modules.namespace), and the rest of the
     * path, without the leading app folder (modules.paths.app_folder), gives the class.
     */
    private function formatModuleNamespace(string $path): string
    {
        $modules_path = rtrim(str_replace('\\', '/', config('modules.paths.modules')), '/');

        $relative_path = Str::of(str_replace('\\', '/', $path))
            ->after($modules_path.'/')
            ->chopEnd('.php');

        $class = $this->strip_app_folder($relative_path->after('/')->toString());

        return $this->module_namespace($relative_path->before('/')->toString()).'\\'.str_replace('/', '\\', $class);
    }

    /**
     * Find the models of every module whose file name matches the pattern.
     *
     * @return Collection<int, string> The model class names.
     */
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

    /**
     * Resolve a short model name given as argument to a module model, then show it.
     */
    #[\Override]
    public function handle(ModelInspector $modelInspector)
    {
        $model = $this->resolveModuleModel($this->argument('model'), $modelInspector);

        if ($model === null) {
            return self::FAILURE;
        }

        $this->input->setArgument('model', $model);

        return parent::handle($modelInspector);
    }

    /**
     * Get the model class to show for the given name.
     *
     * A name that Laravel resolves to an existing class (fully qualified, or in the app models
     * namespace) is kept. Otherwise the name, relative to the models directory of a module
     * ("Post", "Admin/Post" or "Admin\\Post"), is looked up in every module. One match is used,
     * no match keeps the name so Laravel reports it, and several matches are offered as a
     * choice, or fail without interaction. Null means the command must fail.
     */
    private function resolveModuleModel(string $model, ModelInspector $inspector): ?string
    {
        $app_class = (fn (string $model): string => $this->qualifyModel($model))->call($inspector, $model);

        $path = str_replace('\\', '/', ltrim($model, '\\/'));

        if (class_exists($app_class) || $path === '' || strpbrk($path, '*?[') !== false) {
            return $model;
        }

        $candidates = $this->findModels($path)->unique()->values();

        if ($candidates->count() <= 1) {
            return $candidates->first() ?? $model;
        }

        if ($this->input->isInteractive()) {
            return (string) select(
                label: "Model [{$model}] exists in several modules. Select one",
                options: $candidates->all(),
            );
        }

        $this->components->error(sprintf(
            'Model [%s] is ambiguous, it exists in several modules: %s. Pass the fully qualified class name.',
            $model,
            $candidates->implode(', '),
        ));

        return null;
    }

    #[\Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'model' => fn () => search(
                label: 'Select Model',
                options: fn (string $search_value) => $this->findModels(
                    Str::of($search_value)->wrap('', '*')
                )->toArray(),
                placeholder: 'type some thing',
                required: 'You must select one Model',
            ),
        ];
    }
}
