<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Migrations\NameParser;
use Nwidart\Modules\Support\Migrations\SchemaParser;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrationMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration for the specified module.';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The migration name will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be created.'],
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
            ['fields', null, InputOption::VALUE_OPTIONAL, 'The specified fields table.', null],
            ['plain', null, InputOption::VALUE_NONE, 'Create plain migration.'],
        ];
    }

    /**
     * Get schema parser.
     *
     * @return SchemaParser
     */
    public function getSchemaParser()
    {
        return new SchemaParser($this->option('fields'));
    }

    /**
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function getTemplateContents()
    {
        $parser = new NameParser($this->argument('name'));

        if ($parser->isCreate()) {
            return Stub::create('/migration/create.stub', [
                'class' => $this->getClass(),
                'table' => $parser->getTableName(),
                'fields' => $this->getSchemaParser()->render(),
                'module' => $this->getModuleName(),
                'module_namespace' => $this->laravel['modules']->config('namespace'),
            ]);
        } elseif ($parser->isAdd()) {
            return Stub::create('/migration/add.stub', [
                'class' => $this->getClass(),
                'table' => $parser->getTableName(),
                'fields_up' => $this->getSchemaParser()->up(),
                'fields_down' => $this->getSchemaParser()->down(),
                'module' => $this->getModuleName(),
                'module_namespace' => $this->laravel['modules']->config('namespace'),
            ]);
        } elseif ($parser->isDelete()) {
            return Stub::create('/migration/delete.stub', [
                'class' => $this->getClass(),
                'table' => $parser->getTableName(),
                'fields_down' => $this->getSchemaParser()->up(),
                'fields_up' => $this->getSchemaParser()->down(),
                'module' => $this->getModuleName(),
                'module_namespace' => $this->laravel['modules']->config('namespace'),
            ]);
        } elseif ($parser->isDrop()) {
            return Stub::create('/migration/drop.stub', [
                'class' => $this->getClass(),
                'table' => $parser->getTableName(),
                'fields' => $this->getSchemaParser()->render(),
                'module' => $this->getModuleName(),
                'module_namespace' => $this->laravel['modules']->config('namespace'),
            ]);
        }

        return Stub::create('/migration/plain.stub', [
            'class' => $this->getClass(),
        ]);
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('migration');

        return $path.$generatorPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return date('Y_m_d_His_').$this->getSchemaName();
    }

    /**
     * @return array|string
     */
    private function getSchemaName()
    {
        return $this->argument('name');
    }

    /**
     * @return string
     */
    private function getClassName()
    {
        return Str::studly($this->argument('name'));
    }

    public function getClass()
    {
        return $this->getClassName();
    }

    /**
     * Run the command.
     */
    public function handle(): int
    {

        $this->components->info('Creating migration...');

        if (parent::handle() === E_ERROR) {
            return E_ERROR;
        }

        if (app()->environment() === 'testing') {
            return 0;
        }

        return 0;
    }
}
