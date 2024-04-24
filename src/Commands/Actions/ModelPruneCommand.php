<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

use function Laravel\Prompts\multiselect;

use Nwidart\Modules\Facades\Module;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ModelPruneCommand extends PruneCommand implements PromptsForMissingInput
{
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

    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->option('all')) {
            $input->setArgument('module', [self::ALL]);

            return;
        }

        $selected_item = multiselect(
            label   : 'Select Modules',
            options : [
                self::ALL,
                ...array_keys(Module::all()),
            ],
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
     * @return Collection
     */
    protected function models(): Collection
    {
        if (! empty($models = $this->option('model'))) {
            return collect($models)->filter(function ($model) {
                return class_exists($model);
            })->values();
        }

        $except = $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        if ($this->argument('module') == [self::ALL]) {
            $path = sprintf(
                '%s/*/%s',
                config('modules.paths.modules'),
                config('modules.paths.generator.model.path')
            );
        } else {
            $path = sprintf(
                '%s/{%s}/%s',
                config('modules.paths.modules'),
                collect($this->argument('module'))->implode(','),
                config('modules.paths.generator.model.path')
            );
        }

        return collect(Finder::create()->in($path)->files()->name('*.php'))
            ->map(function ($model) {

                $namespace = config('modules.namespace');

                return $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(config('modules.paths.modules')))
                );
            })->values()
            ->when(! empty($except), function ($models) use ($except) {
                return $models->reject(function ($model) use ($except) {
                    return in_array($model, $except);
                });
            })->filter(function ($model) {
                return class_exists($model);
            })->filter(function ($model) {
                return $this->isPrunable($model);
            })->values();
    }

}
