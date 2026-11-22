<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Prompts\Exceptions\NonInteractiveValidationException;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Nwidart\Modules\Traits\PathNamespace;
use Nwidart\Modules\Traits\ValidatesModuleArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'module:prune')]
class ModelPruneCommand extends PruneCommand implements PromptsForMissingInput
{
    use InteractsWithModuleRepository;
    use PathNamespace;
    use ValidatesModuleArgument;

    public const ALL = 'All';

    protected $name = 'module:prune';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'module:prune {module?*}
                                {--all : Check all Modules}
                                {--model=* : Class names of the models to be pruned, or their names in the model folder of the selected modules}
                                {--except=* : Class names of the models to be excluded from pruning, or their names in the model folder of the selected modules}
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
     * The selected modules of the current run.
     *
     * @var Collection<int, \Nwidart\Modules\Module>
     */
    private Collection $modules;

    /**
     * The model classes given with --model, resolved.
     *
     * @var array<int, string>
     */
    private array $models = [];

    /**
     * The model classes given with --except, resolved.
     *
     * @var array<int, string>
     */
    private array $except = [];

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

        try {
            $selected_item = multiselect(
                label   : 'Select Modules',
                options : collect(Module::allEnabled())
                    ->map(fn (\Nwidart\Modules\Module $module) => $module->getName())
                    ->prepend(self::ALL)
                    ->values()
                    ->toArray(),
                required: 'You must select at least one module',
            );
        } catch (NonInteractiveValidationException) {
            // Prompts cannot ask without a TTY, even when --no-interaction is not given.
            // handle() then reports the missing module.
            return;
        }

        $input->setArgument(
            'module',
            value: in_array(self::ALL, $selected_item)
                ? [self::ALL]
                : $selected_item
        );
    }

    /**
     * Check the modules and the --model and --except values, then prune.
     *
     * Every check runs before anything is pruned: a missing or unknown module,
     * --model combined with --except, and a --model or --except value that is
     * neither a class nor a model of the selected modules.
     *
     * @throws InvalidArgumentException When --model and --except are combined.
     */
    #[\Override]
    public function handle(Dispatcher $events): int
    {
        if (! $this->hasModulesToRun() || ! $this->validateModules()) {
            return self::FAILURE;
        }

        $models = (array) $this->option('model');
        $except = (array) $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --model and --except options cannot be combined.');
        }

        $this->modules = $this->selectedModules();

        [$this->models, $unknown_models] = $this->resolveModels($models);
        [$this->except, $unknown_except] = $this->resolveModels($except);

        $unknown = array_values(array_unique([...$unknown_models, ...$unknown_except]));

        if (! empty($unknown)) {
            $this->components->error(count($unknown) === 1
                ? "Model [{$unknown[0]}] does not exist in the selected modules."
                : 'Models ['.implode(', ', $unknown).'] do not exist in the selected modules.');

            return self::FAILURE;
        }

        parent::handle($events);

        return self::SUCCESS;
    }

    /**
     * Determine the models that should be pruned.
     *
     * handle() checked and resolved the selected modules and the --model and --except values.
     *
     * @return Collection<int, string> The model class names.
     *
     * @phpstan-return Collection<int, covariant string>
     */
    #[\Override]
    protected function models(): Collection
    {
        if (! empty($this->models)) {
            return collect($this->models);
        }

        return $this->modules
            ->flatMap(fn (\Nwidart\Modules\Module $module) => $this->modelsInModule($module))
            ->when(! empty($this->except), fn (Collection $models) => $models->reject(fn ($model) => in_array($model, $this->except)))
            ->filter(fn ($model) => $this->isPrunable($model))
            ->values();
    }

    /**
     * Resolve --model or --except values to model classes.
     *
     * A value that is an existing class is kept. Any other value is a name relative to the
     * model folder of a module ("Post", "Admin/Post" or "Admin\\Post", compared without regard
     * to case, like PHP class names) and stands for that model in every selected module.
     *
     * @param  array<int, string>  $values
     * @return array{0: array<int, string>, 1: array<int, string>} The unique model classes, and the values that match no model.
     */
    private function resolveModels(array $values): array
    {
        if (empty($values)) {
            return [[], []];
        }

        $available = $this->modules
            ->flatMap(fn (\Nwidart\Modules\Module $module) => $this->modelsInModuleByName($module)
                ->map(fn (string $class, string $name) => ['name' => strtolower($name), 'class' => $class])
                ->values())
            ->filter(fn (array $model) => class_exists($model['class']));

        $models = [];
        $unknown = [];

        foreach ($values as $value) {
            $value = (string) $value;

            if (class_exists($value)) {
                $models[] = $value;

                continue;
            }

            $name = strtolower(str_replace('/', '\\', trim($value, '\\/')));
            $matches = $available->where('name', $name)->pluck('class');

            if ($matches->isEmpty()) {
                $unknown[] = $value;
            }

            array_push($models, ...$matches->all());
        }

        return [array_values(array_unique($models)), $unknown];
    }

    /**
     * Get the given modules that must exist: the module argument without "All",
     * which --all and the prompt put there for the enabled modules.
     *
     * @return array<int, mixed>
     */
    protected function moduleNamesToValidate(): array
    {
        return array_values(array_filter(
            (array) $this->argument('module'),
            fn ($module) => $module !== self::ALL,
        ));
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
        return $this->modelsInModuleByName($module)->values();
    }

    /**
     * Get the class names of the models in a module, keyed by their names relative
     * to the models directory, such as "Post" or "Admin\\Post".
     * A missing models directory yields no models.
     *
     * @return Collection<string, non-falsy-string>
     */
    private function modelsInModuleByName(\Nwidart\Modules\Module $module): Collection
    {
        $directory = $module->getPath();
        $models_path = $directory.'/'.trim(config('modules.paths.generator.model.path'), '/');

        if (! is_dir($models_path) || ($module_root = realpath($directory)) === false) {
            return collect();
        }

        $namespace = $this->module_namespace($module->getStudlyName());
        $app_folder = $this->app_path().'/';

        return collect(Finder::create()->in($models_path)->files()->name('*.php'))
            ->mapWithKeys(function (SplFileInfo $file) use ($module_root, $namespace, $app_folder) {
                $relative_path = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    Str::after($file->getRealPath(), $module_root.DIRECTORY_SEPARATOR)
                );

                $class = Str::of($relative_path)->chopStart($app_folder)->chopEnd('.php')->replace('/', '\\');

                $name = Str::of(str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname()))
                    ->chopEnd('.php')
                    ->replace('/', '\\')
                    ->toString();

                return [$name => $namespace.'\\'.$class];
            });
    }
}
