<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\Command;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand;
use Nwidart\Modules\Traits\WritesJsonOutput;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ListGeneratorsCommand extends Command
{
    use WritesJsonOutput;

    /**
     * The group of each package generator. A generator that is not listed is in the "other" group.
     *
     * @var array<string, string>
     */
    public const GROUPS = [
        'module:make' => 'module',

        'module:make-model' => 'data',
        'module:make-migration' => 'data',
        'module:make-factory' => 'data',
        'module:make-seed' => 'data',
        'module:make-observer' => 'data',
        'module:make-scope' => 'data',
        'module:make-cast' => 'data',
        'module:make-policy' => 'data',
        'module:make-repository' => 'data',

        'module:make-controller' => 'http',
        'module:make-request' => 'http',
        'module:make-resource' => 'http',
        'module:make-middleware' => 'http',
        'module:make-rule' => 'http',
        'module:route-provider' => 'http',

        'module:make-event' => 'events',
        'module:make-listener' => 'events',
        'module:make-job' => 'events',
        'module:make-job-middleware' => 'events',
        'module:make-notification' => 'events',
        'module:make-mail' => 'events',
        'module:make-channel' => 'events',
        'module:make-event-provider' => 'events',

        'module:make-view' => 'views',
        'module:make-component' => 'views',
        'module:make-component-view' => 'views',
        'module:make-inertia-page' => 'views',
        'module:make-inertia-component' => 'views',

        'module:make-class' => 'classes',
        'module:make-action' => 'classes',
        'module:make-service' => 'classes',
        'module:make-interface' => 'classes',
        'module:make-trait' => 'classes',
        'module:make-enum' => 'classes',
        'module:make-helper' => 'classes',
        'module:make-exception' => 'classes',

        'module:make-command' => 'console',
        'module:make-config' => 'console',
        'module:make-provider' => 'console',
        'module:make-replacement' => 'console',
        'module:make-health-indicator' => 'console',

        'module:make-test' => 'tests',
    ];

    /**
     * The group of a generator that is not in GROUPS, such as an app's own generator.
     */
    public const OTHER_GROUP = 'other';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:generators
        {--json : Print the generators as one JSON document}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the module generators with their arguments and options.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $generators = $this->generators();

        if ($this->enterJsonMode()) {
            $this->writeJson(['generators' => $generators]);

            return self::SUCCESS;
        }

        $this->table(
            ['Command', 'Group', 'Description'],
            array_map(fn (array $generator) => [$generator['name'], $generator['group'], $generator['description']], $generators),
        );

        return self::SUCCESS;
    }

    /**
     * Describe every registered generator: module:make and each GeneratorCommand, sorted by name.
     *
     * @return list<array{name: string, group: string, description: string, arguments: list<array<string, mixed>>, options: list<array<string, mixed>>}>
     */
    protected function generators(): array
    {
        $generators = [];

        foreach ($this->getApplication()?->all() ?? [] as $command) {
            if ($command instanceof LazyCommand) {
                $command = $command->getCommand();
            }

            if (! $command instanceof GeneratorCommand && ! $command instanceof ModuleMakeCommand) {
                continue;
            }

            $name = (string) $command->getName();

            // all() lists a command once per alias.
            $generators[$name] ??= $this->describe($command, $name);
        }

        ksort($generators);

        return array_values($generators);
    }

    /**
     * Describe one generator from its own definition, without the application-wide options.
     *
     * @return array{name: string, group: string, description: string, arguments: list<array<string, mixed>>, options: list<array<string, mixed>>}
     */
    protected function describe(SymfonyCommand $command, string $name): array
    {
        $definition = $command->getNativeDefinition();
        $application = $this->getApplication()?->getDefinition();

        // The native definition holds no application-wide option; the filter keeps it that way.
        $options = array_filter(
            $definition->getOptions(),
            fn (InputOption $option) => ! $application?->hasOption($option->getName()),
        );

        return [
            'name' => $name,
            'group' => self::GROUPS[$name] ?? self::OTHER_GROUP,
            'description' => $command->getDescription(),
            'arguments' => array_values(array_map(fn (InputArgument $argument) => [
                'name' => $argument->getName(),
                'required' => $argument->isRequired(),
                'array' => $argument->isArray(),
                'default' => $argument->getDefault(),
                'description' => $argument->getDescription(),
            ], $definition->getArguments())),
            'options' => array_values(array_map(fn (InputOption $option) => [
                'name' => $option->getName(),
                'shortcut' => $option->getShortcut(),
                'accepts_value' => $option->acceptValue(),
                'value_required' => $option->isValueRequired(),
                'array' => $option->isArray(),
                'negatable' => $option->isNegatable(),
                'default' => $option->getDefault(),
                'description' => $option->getDescription(),
            ], $options)),
        ];
    }
}
