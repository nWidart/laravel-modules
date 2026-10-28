<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Module;
use Nwidart\Modules\Traits\PathNamespace;
use ReflectionClass;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ListCommands extends BaseCommand
{
    use PathNamespace;

    protected $name = 'module:list-commands';

    protected $description = 'List all commands in the specified module(s)';

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $commands = $this->findCommands($module);

        if (empty($commands)) {
            $this->components->info("No commands found in module <fg=cyan;options=bold>{$module->getName()}</>");

            return;
        }

        // Group commands by directory
        $groupedCommands = [];
        foreach ($commands as $command) {
            $directory = $this->getDirectoryFromNamespace($command['namespace']);
            if (! isset($groupedCommands[$directory])) {
                $groupedCommands[$directory] = [];
            }
            $groupedCommands[$directory][] = $command;
        }

        // Display commands by group
        foreach ($groupedCommands as $directory => $dirCommands) {
            $this->components->twoColumnDetail("<fg=yellow>{$directory}</>", '');

            foreach ($dirCommands as $command) {
                $name = $command['name'] ?: '<fg=red>Unknown</>';
                $this->components->success("  <fg=green>{$name}</>");
            }

            $this->newLine();
        }
    }

    /**
     * Find all commands in a module
     *
     * @param  Module  $module
     * @return list<array<string, string|null>>
     */
    protected function findCommands($module): array
    {
        $commands = [];
        $moduleName = $module->getName();
        $moduleNamespace = $this->getModuleNamespace($moduleName);

        // Check possible command paths
        $possiblePaths = [
            $module->getExtraPath('Commands'),
            $module->getExtraPath('Console/Commands'),
            $module->getAppPath().'/Commands',
            $module->getAppPath().'/Console',
            $module->getAppPath().'/Console/Commands',
        ];

        // The paths overlap (Console contains Console/Commands, and the app path is the
        // module root when the app folder does not exist), so list each file only once.
        $files = collect($possiblePaths)
            ->filter(fn (string $path) => is_dir($path))
            ->flatMap(fn (string $path) => File::allFiles($path))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->unique(fn ($file) => $file->getRealPath());

        $modulePath = str_replace('\\', '/', realpath($module->getPath()) ?: $module->getPath());

        foreach ($files as $file) {
            // Get the class name from the file path, relative to the module root
            $relativePath = Str::after(str_replace('\\', '/', $file->getRealPath()), $modulePath.'/');
            $className = $this->getClassNameFromPath($relativePath, $moduleNamespace);

            // Try to get command information
            $commandInfo = $this->getCommandInfo($className);

            if ($commandInfo) {
                $commands[] = $commandInfo;
            }
        }

        return $commands;
    }

    /**
     * Get module namespace
     */
    protected function getModuleNamespace(string $moduleName): string
    {
        return config('modules.namespace', 'Modules').'\\'.$moduleName;
    }

    /**
     * Convert a file path to a class name with namespace
     */
    protected function getClassNameFromPath(string $path, string $moduleNamespace): string
    {
        // Normalise separators and remove the .php extension
        $path = Str::of(str_replace('\\', '/', $path))->chopEnd('.php')->toString();

        // Classes in the app folder (modules.paths.app_folder) sit directly under the module namespace
        $path = $this->strip_app_folder($path);

        return $moduleNamespace.'\\'.str_replace('/', '\\', $path);
    }

    /**
     * Get command information from class
     *
     * @return array<string, string|null>|null
     */
    protected function getCommandInfo(string $className): ?array
    {
        try {
            // Extract the short class name from the fully qualified class name
            $shortClassName = $this->getShortClassName($className);

            if (! class_exists($className)) {
                return [
                    'class' => $className,
                    'name' => $shortClassName,
                    'namespace' => $this->getNamespaceFromClass($className),
                ];
            }

            $reflection = new ReflectionClass($className);

            // Skip if not a command class
            if (! $reflection->isSubclassOf(SymfonyCommand::class) &&
                ! $reflection->isSubclassOf('Illuminate\Console\Command')) {
                return null;
            }

            // Skip if the class is not instantiable or has required constructor parameters
            if (! $reflection->isInstantiable() || $reflection->getConstructor()?->getNumberOfRequiredParameters() > 0) {
                return [
                    'class' => $className,
                    'name' => $shortClassName,
                    'namespace' => $reflection->getNamespaceName(),
                ];
            }

            // Create a proper instance of the command
            $commandInstance = $reflection->newInstance();

            // Get name directly from the instance
            $name = $commandInstance->getName();

            return [
                'class' => $className,
                'name' => $name ?? $shortClassName,
                'namespace' => $reflection->getNamespaceName(),
            ];
        } catch (\Throwable $e) {
            // If we can't instantiate the class, just return basic info with the class name
            return [
                'class' => $className,
                'name' => $this->getShortClassName($className),
                'namespace' => $this->getNamespaceFromClass($className),
            ];
        }
    }

    /**
     * Get short class name from fully qualified class name
     */
    protected function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * Get namespace from class name
     */
    protected function getNamespaceFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        array_pop($parts); // Remove the class name

        return implode('\\', $parts);
    }

    /**
     * Get directory name from namespace
     */
    protected function getDirectoryFromNamespace(string $namespace): string
    {
        $parts = explode('\\', $namespace);

        // Look for Commands or Console\Commands in the namespace
        $commandsIndex = array_search('Commands', $parts);
        if ($commandsIndex !== false) {
            // If we found 'Commands', check if it's preceded by 'Console'
            if ($commandsIndex > 0 && $parts[$commandsIndex - 1] === 'Console') {
                return 'Console/Commands';
            }

            return 'Commands';
        }

        // Default to the last two parts of the namespace
        return implode('/', array_slice($parts, -2, 2));
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Listing commands...';
    }
}
