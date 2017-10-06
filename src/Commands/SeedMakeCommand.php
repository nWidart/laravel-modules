<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\CanClearModulesCache;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SeedMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait, CanClearModulesCache;

    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new seeder for the specified module.';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of seeder will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
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
     * @return mixed
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/seeder.stub', [
            'NAME' => $this->getSeederName(),
            'MODULE' => $this->getModuleName(),
            'NAMESPACE' => $this->getClassNamespace($module),

        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $this->clearCache();

        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $seederPath = GenerateConfigReader::read('seeder');

        return $path . $seederPath->getPath() . '/' . $this->getSeederName() . '.php';
    }

    /**
     * Get seeder name.
     *
     * @return string
     */
    private function getSeederName()
    {
        $end = $this->option('master') ? 'DatabaseSeeder' : 'TableSeeder';

        return Str::studly($this->argument('name')) . $end;
    }
}
