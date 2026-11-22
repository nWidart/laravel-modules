<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Console\Prohibitable;
use Illuminate\Console\View\TaskResult;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class MigrateRefreshCommand extends BaseCommand implements ConfirmableCommand
{
    // Its own prohibition flag: BaseCommand's flag is shared by all its subclasses.
    use Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback & re-migrate the modules migrations.';

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        // handle() already confirmed, so the nested calls are forced and do not ask again
        $this->components->task("Refreshing Migration {$module->getName()} module", function () use ($module) {
            $calls = ['module:migrate-reset', 'module:migrate'];

            if ($this->option('seed')) {
                $calls[] = 'module:seed';
            }

            foreach ($calls as $command) {
                $status = $this->call($command, array_filter([
                    'module' => $module->getStudlyName(),
                    '--database' => $this->option('database'),
                    '--force' => true,
                ]));

                if ($status !== self::SUCCESS) {
                    $this->markAsFailed();

                    return TaskResult::Failure->value;
                }
            }

            return TaskResult::Success->value;
        });
    }

    /**
     * Refresh the enabled modules ordered by priority, like the other database commands.
     * Each module is reset and migrated before the next one, so the order is ascending,
     * as for module:migrate.
     *
     * @return array<int, string>
     */
    #[\Override]
    protected function getModuleNames(InputInterface $input): array
    {
        return $this->getEnabledModuleNames('asc');
    }

    /**
     * Get the console command options.
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
        ];
    }
}
