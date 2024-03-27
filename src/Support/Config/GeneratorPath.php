<?php

namespace Nwidart\Modules\Support\Config;

use Illuminate\Support\Str;

class GeneratorPath
{
    private $path;
    private $generate;
    private $namespace;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path      = $config['path'];
            $this->generate  = $config['generate'];
            $this->namespace = $config['namespace'] ?? $this->pathNamespace($config['path']);

            return;
        }

        $this->path      = $config;
        $this->generate  = (bool) $config;
        $this->namespace = $config;
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

    private function pathNamespace($path)
    {
        return Str::of(trim($path, '/'))->replace('/', '\\')->title();
    }
}
