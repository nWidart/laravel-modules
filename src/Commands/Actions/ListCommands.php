<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Commands\BaseCommand;
use ReflectionClass;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ListCommands extends BaseCommand
{
    protected $name = 'module:list-commands';

    protected $description = 'List all commands in the specified module(s)';

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
     * @return array<string, array<string, string|null>>
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

        foreach ($possiblePaths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = File::allFiles($path);

            foreach ($files as $file) {
                // Skip if not a PHP file
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Get the class name from the file path
                $relativePath = str_replace($module->getPath().'/', '', $file->getPathname());
                $className = $this->getClassNameFromPath($relativePath, $moduleNamespace);

                // Try to get command information
                $commandInfo = $this->getCommandInfo($className);

                if ($commandInfo) {
                    $commands[] = $commandInfo;
                }
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
        // Remove .php extension
        $path = str_replace('.php', '', $path);

        // Convert directory separators to namespace separators
        $path = str_replace('/', '\\', $path);

        // If the path starts with app/, remove it and prepend the module namespace
        if (Str::startsWith($path, 'app\\')) {
            $path = $moduleNamespace.'\\'.Str::after($path, 'app\\');
        } else {
            $path = $moduleNamespace.'\\'.$path;
        }

        return $path;
    }

    /**
     * Get command information from class
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
            $name = null;

            if (method_exists($commandInstance, 'getName')) {
                $name = $commandInstance->getName();
            }

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

    public function getInfo(): ?string
    {
        return 'Listing commands...';
    }
}
