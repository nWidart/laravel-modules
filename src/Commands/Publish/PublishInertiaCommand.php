<?php

namespace Nwidart\Modules\Commands\Publish;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Support\Stub;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

class PublishInertiaCommand extends Command
{
    protected $name = 'module:publish-inertia';

    protected $description = 'Publish the Inertia app.js to resources/js, configured to resolve pages from all modules.';

    public function handle(): int
    {
        $stub = match ($this->getInertiaFrontend()) {
            'react' => '/inertia/app-react.stub',
            'svelte' => '/inertia/app-svelte.stub',
            default => '/inertia/app-vue.stub',
        };
        $destination = resource_path('js/app.js');
        $files = $this->laravel->make(Filesystem::class);

        if ($files->exists($destination) && ! $this->option('force')) {
            if (! $this->canPrompt()) {
                $this->components->error('resources/js/app.js already exists. Use --force to overwrite.');

                return E_ERROR;
            }

            if (! confirm('resources/js/app.js already exists. Overwrite?', default: false)) {
                $this->components->warn('Nothing was written. resources/js/app.js was left unchanged.');

                return E_ERROR;
            }
        }

        $contents = (new Stub($stub))->render();

        $files->ensureDirectoryExists(resource_path('js'));
        $files->put($destination, $contents);

        $this->components->info('Published Inertia app.js to resources/js/app.js');
        $this->components->info('Make sure your vite.config.js includes resources/js/app.js as an input.');

        return 0;
    }

    /**
     * Whether the overwrite question can be asked.
     *
     * Without a TTY, Prompts would return the default answer without asking,
     * so the command keeps the error that tells the user to pass --force.
     */
    private function canPrompt(): bool
    {
        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->laravel->runningUnitTests() || (defined('STDIN') && stream_isatty(STDIN));
    }

    private function getInertiaFrontend(): string
    {
        if ($this->option('react')) {
            return 'react';
        }
        if ($this->option('svelte')) {
            return 'svelte';
        }
        if ($this->option('vue')) {
            return 'vue';
        }

        return config('modules.inertia.frontend', 'vue');
    }

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['vue', null, InputOption::VALUE_NONE, 'Publish the Vue version of the Inertia app.js.'],
            ['react', null, InputOption::VALUE_NONE, 'Publish the React version of the Inertia app.js.'],
            ['svelte', null, InputOption::VALUE_NONE, 'Publish the Svelte version of the Inertia app.js.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite existing app.js.'],
        ];
    }
}
