<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setting up modules folders for first use.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $code = $this->generateModulesFolder();

        return $this->generateAssetsFolder() | $code;
    }

    /**
     * Generate the modules folder.
     */
    public function generateModulesFolder()
    {
        return $this->generateDirectory(
            $this->laravel['modules']->config('paths.modules'),
            'Modules directory created successfully',
            'Modules directory already exists'
        );
    }

    /**
     * Generate the assets folder.
     */
    public function generateAssetsFolder()
    {
        return $this->generateDirectory(
            $this->laravel['modules']->config('paths.assets'),
            'Assets directory created successfully',
            'Assets directory already exists'
        );
    }

    /**
     * Generate the specified directory by given $dir. A directory that already exists is not an error.
     *
     * @param  string  $error  The message shown when the directory already exists.
     */
    protected function generateDirectory($dir, $success, $error): int
    {
        if (! $this->laravel['files']->isDirectory($dir)) {
            $this->laravel['files']->makeDirectory($dir, 0755, true, true);

            $this->components->info($success);

            return self::SUCCESS;
        }

        $this->components->info($error);

        return self::SUCCESS;
    }
}
