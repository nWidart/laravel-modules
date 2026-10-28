<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Generators\ModuleGenerator;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ModuleMakeCommand extends Command implements PromptsForMissingInput
{
    use InteractsWithModuleRepository;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $names = $this->argument('name');
        $success = true;
        $generated = false;

        foreach ($names as $name) {
            // ModuleGenerator::getName() studly-cases the name; it becomes the folder and the namespace.
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', Str::studly($name)) !== 1) {
                $this->components->error("The module name [{$name}] is not valid. Use letters, digits, \"-\", \"_\" or spaces, starting with a letter.");
                $success = false;

                continue;
            }

            $code = (new ModuleGenerator($name))
                ->setFilesystem($this->laravel->make(Filesystem::class))
                ->setModule($this->moduleRepository())
                ->setConfig($this->laravel->make(ConfigRepository::class))
                ->setActivator($this->laravel->make(ActivatorInterface::class))
                ->setConsole($this)
                ->setComponent($this->components)
                ->setForce($this->option('force'))
                ->setType($this->getModuleType())
                ->setInertia((bool) $this->option('inertia'))
                ->setActive(! $this->option('disabled'))
                ->setVendor($this->option('author-vendor'))
                ->setAuthor($this->option('author-name'), $this->option('author-email'))
                ->generate();

            if ($code === E_ERROR) {
                $success = false;
            } else {
                $generated = true;
            }
        }

        // Nothing was created, so there are no new service providers to discover.
        if (! $generated) {
            return E_ERROR;
        }

        // to discover new service providers
        $result = Process::path(base_path())
            ->command('composer dump-autoload')
            ->run();

        if ($result->failed()) {
            $output = trim($result->errorOutput()) ?: trim($result->output());

            // Composer ends an error with the command synopsis ("dump-autoload [-o|--optimize] ..."): drop it.
            $output = trim((string) preg_replace('/\n\s*dump-autoload \[[^\n]*$/', '', $output));

            $this->components->error('composer dump-autoload failed.');

            if ($output !== '') {
                $this->line($output);
                $this->newLine();
            }

            $this->components->info('Fix the module (for example its composer.json), then run [composer dump-autoload].');

            return E_ERROR;
        }

        return $success ? 0 : E_ERROR;
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['name', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The names of modules will be created.'],
        ];
    }

    #[\Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'What is the name of the module?',
        ];
    }

    #[\Override]
    protected function getOptions()
    {
        return [
            ['plain', 'p', InputOption::VALUE_NONE, 'Generate a plain module (without some resources).'],
            ['api', null, InputOption::VALUE_NONE, 'Generate an api module.'],
            ['web', null, InputOption::VALUE_NONE, 'Generate a web module.'],
            ['inertia', null, InputOption::VALUE_NONE, 'Generate an Inertia module (Inertia controller + JS pages, no Blade views).'],
            ['disabled', 'd', InputOption::VALUE_NONE, 'Do not enable the module at creation.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when the module already exists.'],
            ['author-name', null, InputOption::VALUE_OPTIONAL, 'Author name.'],
            ['author-email', null, InputOption::VALUE_OPTIONAL, 'Author email.'],
            ['author-vendor', null, InputOption::VALUE_OPTIONAL, 'Author vendor.'],
        ];
    }

    /**
     * Get module type .
     *
     * @return string
     */
    private function getModuleType()
    {
        $isPlain = $this->option('plain');
        $isApi = $this->option('api');

        if ($isPlain && $isApi) {
            return 'web';
        }
        if ($isPlain) {
            return 'plain';
        } elseif ($isApi) {
            return 'api';
        } else {
            return 'web';
        }
    }
}
