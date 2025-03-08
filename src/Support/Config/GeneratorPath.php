<?php

namespace Nwidart\Modules\Support\Config;

use Nwidart\Modules\Traits\PathNamespace;

class GeneratorPath
{
    use PathNamespace;

    private $path;

    private $generate;

    private $namespace;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = $this->path($config['path']);
            $this->namespace = $config['namespace'] ?? $this->namespace($config['path']);
            $this->generate = $config['generate'];

            return;
        }

        $this->path = $this->path($config ?? '');
        $this->namespace = $this->namespace($config ?? '');
        $this->generate = (bool) $config;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function generate(): bool
    {
        return $this->generate;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }
}
