<?php

namespace Nwidart\Modules\Commands\Publish;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ResolvesInertiaFrontend;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

class PublishInertiaCommand extends Command
{
    use ResolvesInertiaFrontend;

    protected $name = 'module:publish-inertia';

    protected $description = 'Publish the Inertia app.js to resources/js, configured to resolve pages from all modules. Use --ssr for server-side rendering.';

    public function handle(): int
    {
        if ($this->reportConflictingInertiaFrontends()) {
            return E_ERROR;
        }

        $stubs = $this->option('ssr') ? $this->ssrStubs() : $this->appStubs();
        $files = $this->laravel->make(Filesystem::class);

        $problems = $this->destinationProblems(array_keys($stubs));

        if ($problems !== []) {
            $this->components->error(implode(' ', $problems).' Nothing was written.');

            return E_ERROR;
        }

        $existing = array_values(array_filter(
            array_keys($stubs),
            fn (string $file) => $files->exists(resource_path('js/'.$file)),
        ));

        if ($existing !== [] && ! $this->option('force')) {
            $named = Arr::join(array_map(fn (string $file) => 'resources/js/'.$file, $existing), ', ', ' and ');
            $exist = count($existing) === 1 ? 'already exists' : 'already exist';

            if (! $this->canPrompt()) {
                $this->components->error("{$named} {$exist}. Use --force to overwrite.");

                return E_ERROR;
            }

            if (! confirm("{$named} {$exist}. Overwrite?", default: false)) {
                $was = count($existing) === 1 ? 'was' : 'were';
                $this->components->warn("Nothing was written. {$named} {$was} left unchanged.");

                return E_ERROR;
            }
        }

        $contents = array_map(fn (Stub $stub) => $stub->render(), $stubs);

        $files->ensureDirectoryExists(resource_path('js'));

        foreach ($contents as $file => $content) {
            $files->put(resource_path('js/'.$file), $content);
        }

        if ($this->option('ssr')) {
            $this->components->info('Published Inertia app.js, ssr.js and inertia-pages.js to resources/js');
            $this->components->bulletList([
                'Make sure your vite.config.js includes resources/js/app.js as an input',
                'Install the Inertia Vite plugin: npm install -D @inertiajs/vite',
                'Add inertia() from @inertiajs/vite to the vite.config.js plugins, after laravel(...)',
                'Set the build script in package.json to "vite build && vite build --ssr"',
                'In production, run php artisan inertia:start-ssr',
            ]);

            return 0;
        }

        $this->components->info('Published Inertia app.js to resources/js/app.js');
        $this->components->info('Make sure your vite.config.js includes resources/js/app.js as an input.');

        return 0;
    }

    /**
     * The client-only app.js, keyed by its file name in resources/js.
     *
     * @return array<string, Stub>
     */
    private function appStubs(): array
    {
        $stub = match ($this->getInertiaFrontend()) {
            'react' => '/inertia/app-react.stub',
            'svelte' => '/inertia/app-svelte.stub',
            default => '/inertia/app-vue.stub',
        };

        return ['app.js' => new Stub($stub)];
    }

    /**
     * The SSR-ready app.js, the ssr.js entry and the page resolver they share,
     * keyed by their file names in resources/js.
     *
     * @return array<string, Stub>
     */
    private function ssrStubs(): array
    {
        [$package, $extension] = match ($this->getInertiaFrontend()) {
            'react' => ['@inertiajs/react', 'jsx'],
            'svelte' => ['@inertiajs/svelte', 'svelte'],
            default => ['@inertiajs/vue3', 'vue'],
        };

        return [
            'app.js' => new Stub('/inertia/ssr/app.stub', ['INERTIA_PACKAGE' => $package]),
            'ssr.js' => new Stub('/inertia/ssr/ssr.stub', ['INERTIA_PACKAGE' => $package]),
            'inertia-pages.js' => new Stub('/inertia/ssr/inertia-pages.stub', ['PAGE_EXTENSION' => $extension]),
        ];
    }

    /**
     * Why the files cannot all be written to resources/js.
     *
     * Every destination is checked before any file is written, so a failure
     * never leaves a partial set, such as an app.js that imports an
     * inertia-pages.js that was never written. Symbolic links are refused
     * outright: a write follows them, even when they dangle or point at
     * another destination.
     *
     * @param  list<string>  $files
     * @return list<string>
     */
    private function destinationProblems(array $files): array
    {
        clearstatcache();

        $directory = resource_path('js');

        if (file_exists($directory) && ! is_dir($directory)) {
            return ['resources/js is not a directory.'];
        }

        $problems = [];
        $missing = false;

        foreach ($files as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;
            $name = 'resources/js/'.$file;

            if (is_link($path)) {
                $problems[] = "{$name} is a symbolic link. Replace it with a regular file or remove it.";
            } elseif (is_dir($path)) {
                $problems[] = "{$name} is a directory.";
            } elseif (! file_exists($path)) {
                $missing = true;
            } elseif (! is_writable($path)) {
                $problems[] = "{$name} is not writable.";
            }
        }

        if ($missing && is_dir($directory) && ! is_writable($directory)) {
            $problems[] = 'resources/js is not writable.';
        }

        return $problems;
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

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['vue', null, InputOption::VALUE_NONE, 'Publish the Vue version of the Inertia app.js.'],
            ['react', null, InputOption::VALUE_NONE, 'Publish the React version of the Inertia app.js.'],
            ['svelte', null, InputOption::VALUE_NONE, 'Publish the Svelte version of the Inertia app.js.'],
            ['ssr', null, InputOption::VALUE_NONE, 'Publish an SSR-ready app.js, plus ssr.js and the shared page resolver inertia-pages.js.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the existing files.'],
        ];
    }
}
