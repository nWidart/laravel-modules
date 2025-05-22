<?php

namespace Nwidart\Modules\Support\Config;

use Nwidart\Modules\Traits\PathNamespace;

class GeneratorPath
{
    use PathNamespace {
        path as __path;
        namespace as __namespace;
    }

    private $path;

    private $generate;

    private $namespace;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = $this->__path($config['path']);
            $this->namespace = $config['namespace'] ?? $this->__namespace($config['path']);
            $this->generate = $config['generate'];

            return;
        }

        $this->path = $this->__path($config ?? '');
        $this->namespace = $this->__namespace($config ?? '');
        $this->generate = (bool) $config;
    }

    /**
     * @deprecated use path() instead
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the generator path.
     */
    public function path(string $path = '')
    {
        return $this->__path($this->path.DIRECTORY_SEPARATOR.$path);
    }

    public function getNamespace()
    {
        return $this->studly_namespace($this->namespace);
    }

    /**
     * Get the generator namespace.
     */
    public function namespace(string $namespace = '')
    {
        return $this->__namespace($this->namespace.'\\'.$namespace);
    }

    public function generate(): bool
    {
        return $this->generate;
    }
}
