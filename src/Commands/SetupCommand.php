<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;

class SetupCommand extends Command
{
    use InteractsWithModuleRepository;

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
     *
     * @return int
     */
    public function generateModulesFolder()
    {
        return $this->generateDirectory(
            $this->moduleRepository()->config('paths.modules'),
            'Modules directory created successfully',
            'Modules directory already exists'
        );
    }

    /**
     * Generate the assets folder.
     *
     * @return int
     */
    public function generateAssetsFolder()
    {
        return $this->generateDirectory(
            $this->moduleRepository()->config('paths.assets'),
            'Assets directory created successfully',
            'Assets directory already exists'
        );
    }

    /**
     * Generate the specified directory by given $dir. A directory that already exists is not an error.
     *
     * @param  string  $error  The message shown when the directory already exists.
     * @param  string  $dir
     * @param  string  $success
     */
    protected function generateDirectory($dir, $success, $error): int
    {
        $files = $this->laravel->make(Filesystem::class);

        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true, true);

            $this->components->info($success);

            return self::SUCCESS;
        }

        $this->components->info($error);

        return self::SUCCESS;
    }
}
