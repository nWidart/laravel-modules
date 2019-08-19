<?php

namespace Nwidart\Modules\Support\Config;

class GeneratorPath
{
    private $path;
    private $generate;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = $config['path'];
            $this->generate = $config['generate'];
            $this->namespace = $config['namespace'] ?? $config['path'];

            return;
        }
        $this->path = $config;
        $this->generate = (bool) $config;
        $this->namespace = $config;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function generate() : bool
    {
        return $this->generate;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }
}
