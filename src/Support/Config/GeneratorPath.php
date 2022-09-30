<?php

namespace Nwidart\Modules\Support\Config;

class GeneratorPath
{
    private $path;
    private $generate;
    private $suffix;
    private $namespace;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = $config['path'];
            $this->generate = $config['generate'];
            $this->suffix = $config['suffix'] ?? "";
            $this->namespace = $config['namespace'] ?? $this->convertPathToNamespace($config['path']);

            return;
        }
        $this->path = $config;
        $this->generate = (bool) $config;
        $this->suffix   = "";
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

    public function suffix(): string
    {
        return $this->suffix;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    private function convertPathToNamespace($path)
    {
        return str_replace('/', '\\', $path);
    }
}
