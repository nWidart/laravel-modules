<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        if ($input->getOption('all')) {
            $input->setArgument('module', [self::ALL]);
        }
    }

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
     */
    protected function models(): Collection
    {
        $models = $this->option('model');
        $except = $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --model and --except options cannot be combined.');
        }

        if (! empty($models)) {
            return collect($models)->filter(fn ($model) => class_exists($model))->values();
        }

        return $this->moduleDirectories()
            ->flatMap(fn (string $directory) => $this->modelsInModule($directory))
            ->when(! empty($except), fn (Collection $models) => $models->reject(fn ($model) => in_array($model, $except)))
            ->filter(fn ($model) => $this->isPrunable($model))
            ->values();
    }

    /**
     * Get the root directories of the selected modules.
     */
    protected function moduleDirectories(): Collection
    {
        $modules_path = rtrim(config('modules.paths.modules'), '/\\');
        $modules = collect($this->argument('module'));

        if ($modules->contains(self::ALL)) {
            return collect(glob($modules_path.'/*', GLOB_ONLYDIR) ?: []);
        }

        return $modules->map(fn (string $module) => $modules_path.'/'.$module);
    }

    /**
     * Get the class names of the models in a module directory.
     * A missing models directory yields no models.
     */
    protected function modelsInModule(string $directory): Collection
    {
        $models_path = $directory.'/'.trim(config('modules.paths.generator.model.path'), '/');

        if (! is_dir($models_path) || ($module_root = realpath($directory)) === false) {
            return collect();
        }

        $namespace = $this->module_namespace(basename($module_root));
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
