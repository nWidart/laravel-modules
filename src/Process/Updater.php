<?php

namespace Nwidart\Modules\Process;

use Nwidart\Modules\Module;
use RuntimeException;
use stdClass;

class Updater extends Runner
{
    /**
     * Update the dependencies for the specified module by given the module name.
     *
     * @return void
     */
    public function update(string $module)
    {
        $module = $this->module->findOrFail($module);

        chdir(base_path());

        $this->installRequires($module);
        $this->installDevRequires($module);
        $this->copyScriptsToMainComposerJson($module);
    }

    /**
     * Check if composer should output anything.
     */
    private function isComposerSilenced(): string
    {
        return config('modules.composer.composer-output') === false ? ' --quiet' : '';
    }

    private function installRequires(Module $module): void
    {
        $packages = $module->getComposerAttr('require', []);

        $concatenatedPackages = '';
        foreach ($packages as $name => $version) {
            $concatenatedPackages .= escapeshellarg("{$name}:{$version}").' ';
        }

        if (! empty($concatenatedPackages)) {
            $this->run("composer require {$concatenatedPackages}{$this->isComposerSilenced()}");
        }
    }

    private function installDevRequires(Module $module): void
    {
        $devPackages = $module->getComposerAttr('require-dev', []);

        $concatenatedPackages = '';
        foreach ($devPackages as $name => $version) {
            $concatenatedPackages .= escapeshellarg("{$name}:{$version}").' ';
        }

        if (! empty($concatenatedPackages)) {
            $this->run("composer require --dev {$concatenatedPackages}{$this->isComposerSilenced()}");
        }
    }

    /**
     * Merge the module composer scripts into the app composer.json.
     *
     * The file is decoded to objects, so empty objects such as `"require-dev": {}`
     * stay objects when it is written back. A script may be a string or a list of
     * commands: both are merged into a list without duplicates.
     *
     * @throws RuntimeException
     */
    private function copyScriptsToMainComposerJson(Module $module): void
    {
        $scripts = $module->getComposerAttr('scripts', []);

        if (! is_array($scripts) || $scripts === []) {
            return;
        }

        $path = base_path('composer.json');
        $composer = json_decode(file_get_contents($path));

        if (! $composer instanceof stdClass) {
            throw new RuntimeException("Unable to copy the scripts of module [{$module->getName()}]: [{$path}] is not a valid composer.json file.");
        }

        if (! isset($composer->scripts) || ! is_object($composer->scripts)) {
            $composer->scripts = (object) ($composer->scripts ?? []);
        }

        foreach ($scripts as $key => $script) {
            if (isset($composer->scripts->{$key})) {
                $script = array_values(array_unique(array_merge((array) $composer->scripts->{$key}, (array) $script)));
            }

            $composer->scripts->{$key} = $script;
        }

        file_put_contents($path, json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL);
    }
}
