<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of all modules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->table(['Name', 'Status', 'Order', 'Path'], $this->getRows());
    }

    /**
     * Get table rows.
     *
     * @return array
     */
    public function getRows()
    {
        $rows = [];

        /** @var Module $module */
        foreach ($this->getModules() as $module) {
            $rows[] = [
                $module->getName(),
                $module->isEnabled() ? 'Enabled' : 'Disabled',
                $module->get('order'),
                $module->getPath(),
            ];
        }

        return $rows;
    }

    public function getModules()
    {
        switch ($this->option('only')) {
            case 'enabled':
                return $this->laravel['modules']->getByStatus(1);
                break;

            case 'disabled':
                return $this->laravel['modules']->getByStatus(0);
                break;

            case 'ordered':
                return $this->laravel['modules']->getOrdered($this->option('direction'));
                break;

            default:
                return $this->laravel['modules']->all();
                break;
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['only', 'o', InputOption::VALUE_OPTIONAL, 'Types of modules will be displayed.', null],
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
        ];
    }
}
