<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\PathNamespace;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'module:prune')]
class ModelPruneCommand extends PruneCommand implements PromptsForMissingInput
{
    use PathNamespace;

    public const ALL = 'All';

    protected $name = 'module:prune';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'module:prune {module*}
                                {--all : Check all Modules}
                                {--model=* : Class names of the models to be pruned}
                                {--except=* : Class names of the models to be excluded from pruning}
                                {--path=* : Absolute path(s) to directories where models are located}
                                {--chunk=1000 : The number of models to retrieve per chunk of models to be deleted}
                                {--pretend : Display the number of prunable records found instead of deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune models by module that are no longer needed';

    /**
     * Resolve --all into the module argument. This runs for every input and before the
     * required module argument is validated, unlike promptForMissingArguments().
     */
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        if ($input->getOption('all')) {
            $input->setArgument('module', [self::ALL]);
        }
    }

    #[\Override]
    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if (! empty($input->getArgument('module'))) {
            return;
        }

        $selected_item = multiselect(
            label   : 'Select Modules',
            options : collect(Module::allEnabled())
                ->map(fn (\Nwidart\Modules\Module $module) => $module->getName())
                ->prepend(self::ALL)
                ->values()
                ->toArray(),
            required: 'You must select at least one module',
        );

        $input->setArgument(
            'module',
            value: in_array(self::ALL, $selected_item)
                ? [self::ALL]
                : $selected_item
        );
    }

    /**
     * Determine the models that should be pruned.
     *
     * The selected modules are resolved first, so an unknown module fails
     * before anything is pruned, also when --model is given.
     *
     * @return Collection<int, string> The model class names.
     *
     * @phpstan-return Collection<int, covariant string>
     *
     * @throws ModuleNotFoundException
     */
    #[\Override]
    protected function models(): Collection
    {
        $models = $this->option('model');
        $except = $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --model and --except options cannot be combined.');
        }

        $modules = $this->selectedModules();

        if (! empty($models)) {
            return collect($models)->filter(fn ($model) => class_exists($model))->values();
        }

        return $modules
            ->flatMap(fn (\Nwidart\Modules\Module $module) => $this->modelsInModule($module))
            ->when(! empty($except), fn (Collection $models) => $models->reject(fn ($model) => in_array($model, $except)))
            ->filter(fn ($model) => $this->isPrunable($model))
            ->values();
    }

    /**
     * Resolve the module argument through the module repository.
     * "All" selects the enabled modules; a named module must exist.
     *
     * @return Collection<int, \Nwidart\Modules\Module>
     *
     * @throws ModuleNotFoundException
     */
    protected function selectedModules(): Collection
    {
        $names = collect($this->argument('module'));

        $modules = $names->contains(self::ALL)
            ? collect(Module::allEnabled())
            : $names->map(fn (string $name) => Module::findOrFail($name));

        return $modules
            ->unique(fn (\Nwidart\Modules\Module $module) => $module->getName())
            ->values();
    }

    /**
     * Get the class names of the models in a module.
     * A missing models directory yields no models.
     *
     * @return Collection<int, non-falsy-string>
     */
    protected function modelsInModule(\Nwidart\Modules\Module $module): Collection
    {
        $directory = $module->getPath();
        $models_path = $directory.'/'.trim(config('modules.paths.generator.model.path'), '/');

        if (! is_dir($models_path) || ($module_root = realpath($directory)) === false) {
            return collect();
        }

        $namespace = $this->module_namespace($module->getStudlyName());
        $app_folder = $this->app_path().'/';

        return collect(Finder::create()->in($models_path)->files()->name('*.php'))
            ->map(function (SplFileInfo $file) use ($module_root, $namespace, $app_folder) {
                $relative_path = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    Str::after($file->getRealPath(), $module_root.DIRECTORY_SEPARATOR)
                );

                $class = Str::of($relative_path)->chopStart($app_folder)->chopEnd('.php')->replace('/', '\\');

                return $namespace.'\\'.$class;
            })
            ->values();
    }
}
