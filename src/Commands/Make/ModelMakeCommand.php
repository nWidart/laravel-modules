<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected $argumentName = 'model';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model for the specified module.';

    #[\Override]
    public function handle(): int
    {
        // Resolve --all before the model is rendered: the model stub depends on --factory.
        if ($this->option('all')) {
            $this->input->setOption('controller', true);
            $this->input->setOption('factory', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('request', true);
            $this->input->setOption('resource', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('seed', true);
        }

        if (parent::handle() === E_ERROR) {
            return E_ERROR;
        }

        $this->handleOptionalControllerOption();
        $this->handleOptionalFactoryOption();
        $this->handleOptionalMigrationOption();
        $this->handleOptionalRequestOption();
        $this->handleOptionalResourceOption();
        $this->handleOptionalSeedOption();
        $this->handleOptionalPolicyOption();

        return 0;
    }

    /**
     * Create a proper migration name:
     * ProductDetail: product_details
     * Product: products
     */
    protected function createMigrationName(): string
    {
        $pieces = preg_split('/(?=[A-Z])/', $this->argument('model'), -1, PREG_SPLIT_NO_EMPTY);

        $string = '';
        foreach ($pieces as $i => $piece) {
            if ($i + 1 < count($pieces)) {
                $string .= strtolower($piece).'_';
            } else {
                $string .= Str::plural(strtolower($piece));
            }
        }

        return $string;
    }

    /**
     * Get the console command arguments.
     */
    #[\Override]
    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The name of model will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, 'Flag to create all associated files', null],
            ['attributes', null, InputOption::VALUE_NONE, 'Use Eloquent attributes such as #[Fillable] instead of model properties', null],
            ['controller', 'c', InputOption::VALUE_NONE, 'Flag to create associated controllers', null],
            ['fillable', null, InputOption::VALUE_OPTIONAL, 'The fillable attributes.', null],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model', null],
            ['migration', 'm', InputOption::VALUE_NONE, 'Flag to create associated migrations', null],
            ['request', 'r', InputOption::VALUE_NONE, 'Create a new request for the model', null],
            ['resource', 'R', InputOption::VALUE_NONE, 'Create a new resource for the model', null],
            ['policy', 'p', InputOption::VALUE_NONE, 'Create a new policy for the model', null],
            ['seed', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model', null],
        ];
    }

    /**
     * Create the migration file with the given model if migration flag was used
     */
    protected function handleOptionalMigrationOption(): void
    {
        if ($this->option('migration') === true) {
            $migrationName = 'create_'.$this->createMigrationName().'_table';
            $this->call('module:make-migration', ['name' => $migrationName, 'module' => $this->argument('module')]);
        }
    }

    /**
     * Create the controller file for the given model if controller flag was used
     */
    protected function handleOptionalControllerOption(): void
    {
        if ($this->option('controller') === true) {
            $controllerName = "{$this->getModelName()}Controller";

            $this->call('module:make-controller', array_filter([
                'controller' => $controllerName,
                'module' => $this->argument('module'),
            ]));
        }
    }

    /**
     * Create a seeder file for the model.
     */
    protected function handleOptionalFactoryOption(): void
    {
        if ($this->option('factory') === true) {
            $this->call('module:make-factory', array_filter([
                'name' => $this->getModelName(),
                'module' => $this->argument('module'),
            ]));
        }
    }

    /**
     * Create a request file for the model.
     */
    protected function handleOptionalRequestOption(): void
    {
        if ($this->option('request') === true) {
            $requestName = "{$this->getModelName()}Request";

            $this->call('module:make-request', array_filter([
                'name' => $requestName,
                'module' => $this->argument('module'),
            ]));
        }
    }

    /**
     * Create a resource file for the model.
     */
    protected function handleOptionalResourceOption(): void
    {
        if ($this->option('resource') === true) {
            $resourceName = "{$this->getModelName()}Resource";

            $this->call('module:make-resource', array_filter([
                'name' => $resourceName,
                'module' => $this->argument('module'),
            ]));
        }
    }

    /**
     * Create a seeder file for the model.
     */
    protected function handleOptionalSeedOption(): void
    {
        if ($this->option('seed') === true) {
            $seedName = "{$this->getModelName()}Seeder";

            $this->call('module:make-seed', array_filter([
                'name' => $seedName,
                'module' => $this->argument('module'),
            ]));
        }
    }

    /**
     * Create a policy file for the model.
     */
    protected function handleOptionalPolicyOption(): void
    {
        if ($this->option('policy') === true) {
            $policyName = "{$this->getModelName()}Policy";

            $this->call('module:make-policy', array_filter([
                'name' => $policyName,
                'module' => $this->argument('module'),
            ]));
        }
    }

    #[\Override]
    protected function getTemplateContents(): string
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        $withFactory = $this->option('factory') === true;
        $withAttributes = $this->option('attributes') === true;
        $factoryClass = $withFactory ? $this->getFactoryClass($module) : '';

        return (new Stub('/model.stub', [
            'NAME' => $this->getModelName(),
            'FILLABLE' => $this->getFillable(),
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
            'LOWER_NAME' => $module->getLowerName(),
            'MODULE' => $this->getModuleName(),
            'STUDLY_NAME' => $module->getStudlyName(),
            'MODULE_NAMESPACE' => $this->moduleRepository()->config('namespace'),
            'FACTORY' => $withFactory ? class_basename($factoryClass) : '',
            'FACTORY_CLASS' => $factoryClass,
        ]))->sections([
            'ATTRIBUTES' => $withAttributes,
            'FACTORY' => $withFactory,
            'PROPERTIES' => ! $withAttributes,
        ])->render();
    }

    /**
     * Get the fully qualified class name of the factory that module:make-factory
     * generates for this model.
     *
     * The namespace comes from FactoryMakeCommand itself, so a custom module
     * namespace or factory path/namespace config gives the same class.
     */
    protected function getFactoryClass(Module $module): string
    {
        $name = $this->getModelName();

        $command = $this->laravel->make(FactoryMakeCommand::class);
        $command->setInput(new ArrayInput(
            ['name' => $name, 'module' => $module->getName()],
            $command->getDefinition(),
        ));

        return $command->getClassNamespace($module).'\\'.class_basename($name).'Factory';
    }

    #[\Override]
    protected function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $modelPath = GenerateConfigReader::read('model');

        return $path.$modelPath->getPath().'/'.$this->getModelName().'.php';
    }

    private function getModelName(): string
    {
        return Str::studly($this->argument('model'));
    }

    private function getFillable(): string
    {
        $fillable = $this->option('fillable');

        if (! is_null($fillable)) {
            $arrays = explode(',', $fillable);

            return json_encode($arrays);
        }

        return '[]';
    }

    /**
     * Get default namespace.
     */
    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.model.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.model.path', 'Models'));
    }
}
