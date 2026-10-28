<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\CanClearModulesCache;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SeedMakeCommand extends GeneratorCommand
{
    use CanClearModulesCache;
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    /**
     * The console command name.
     */
    protected $name = 'module:make-seed';

    /**
     * The console command description.
     */
    protected $description = 'Create a new seeder for the specified module.';

    /**
     * Get the console command arguments.
     */
    #[\Override]
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of seeder will be created.'],
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
            [
                'master',
                null,
                InputOption::VALUE_NONE,
                'Indicates the seeder will created is a master database seeder.',
            ],
        ];
    }

    /**
     * Also reject a name that resolves to a class named "Seeder": the generated
     * class would clash with the Illuminate\Database\Seeder class it extends.
     */
    #[\Override]
    protected function validateNameArgument(): ?string
    {
        if (($error = parent::validateNameArgument()) !== null) {
            return $error;
        }

        $name = $this->argument('name');

        if (class_basename($this->getSeederName()) === 'Seeder') {
            return "The name [{$name}] resolves to the class [Seeder], which clashes with the Illuminate\\Database\\Seeder base class.";
        }

        return null;
    }

    #[\Override]
    protected function getTemplateContents(): mixed
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub('/seeder.stub', [
            'NAME' => $this->getSeederName(),
            'MODULE' => $this->getModuleName(),
            'NAMESPACE' => $this->getClassNamespace($module),

        ]))->render();
    }

    #[\Override]
    protected function getDestinationFilePath(): mixed
    {
        $this->clearCache();

        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $seederPath = GenerateConfigReader::read('seeder');

        return $path.$seederPath->getPath().'/'.$this->getSeederName().'.php';
    }

    /**
     * Get the seeder name.
     */
    private function getSeederName(): string
    {
        $string = $this->argument('name');
        $string .= $this->option('master') ? 'Database' : '';
        $suffix = 'Seeder';

        if (! str_contains($string, $suffix)) {
            $string .= $suffix;
        }

        return Str::studly($string);
    }

    /**
     * Get default namespace.
     */
    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.seeder.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.seeder.path', 'Database/Seeders'));
    }
}
