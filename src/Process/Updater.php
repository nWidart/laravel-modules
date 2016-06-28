<?php

namespace Nwidart\Modules\Process;

class Updater extends Runner
{
    /**
     * Update the dependencies for the specified module by given the module name.
     *
     * @param string $module
     */
    public function update($module)
    {
        $module = $this->module->findOrFail($module);

        $packages = $module->getComposerAttr('require', []);

        chdir(base_path());

        foreach ($packages as $name => $version) {
            $package = "\"{$name}:{$version}\"";

            $this->run("composer require {$package}");
        }
    }
}
