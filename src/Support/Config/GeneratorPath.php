<?php

namespace Nwidart\Modules\Support\Config;

use Nwidart\Modules\Traits\PathNamespace;

class GeneratorPath
{
    use PathNamespace;

    /**
     * The generator path. A legacy config holds the path itself, or false to skip the folder.
     */
    private readonly string|bool|null $path;

    private readonly bool $generate;

    private readonly string $namespace;

    /**
     * @param  array<string, mixed>|string|bool|null  $config  The modules.paths.generator.<key> config.
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            // A missing "generate" key means the folder is not scaffolded by module:make.
            $this->path = $config['path'] ?? null;
            $this->generate = (bool) ($config['generate'] ?? false);
            $this->namespace = $config['namespace'] ?? $this->path_namespace($this->strip_app_folder((string) $this->path));

            return;
        }

        $this->path = $config;
        $this->generate = (bool) $config;
        $this->namespace = $this->path_namespace($this->strip_app_folder((string) $config));
    }

    /**
     * @return string|bool|null
     */
    public function getPath()
    {
        return $this->path;
    }

    public function generate(): bool
    {
        return $this->generate;
    }

    /**
     * Whether this generator runs alongside a parent generator.
     * Without its own path config, it follows the parent.
     */
    public function generatesWith(bool $parentGenerated): bool
    {
        return is_null($this->path) ? $parentGenerated : $this->generate;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->studly_namespace($this->namespace);
    }
}
