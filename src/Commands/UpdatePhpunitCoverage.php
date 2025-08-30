<?php

namespace Nwidart\Modules\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use Nwidart\Modules\Traits\PathNamespace;

class UpdatePhpunitCoverage extends Command
{
    use PathNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:update-phpunit-coverage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update phpunit.xml source/include path with enabled modules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $app_path = rtrim($this->app_path(), '/').'/';
        $phpunitXmlPath = base_path('phpunit.xml');
        $modulesStatusPath = config('modules.activators.file.statuses-file', base_path('modules_statuses.json'));

        if (! file_exists($phpunitXmlPath)) {
            $this->error("phpunit.xml file not found: {$phpunitXmlPath}");

            return 100;
        }

        if (! file_exists($modulesStatusPath)) {
            $this->error("Modules statuses file not found: {$modulesStatusPath}");

            return 99;
        }

        $enabledModules = json_decode(file_get_contents($modulesStatusPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Error decoding JSON from {$modulesStatusPath}: ".json_last_error_msg());

            return 98;
        }

        $modulesPath = rtrim(config('modules.paths.modules', base_path('Modules')), '/').'/';
        $moduleDirs = [];

        foreach ($enabledModules as $module => $status) {
            if ($status) { // Only add enabled modules
                $moduleDir = $modulesPath.$module.'/'.$app_path;
                if (is_dir($moduleDir)) {
                    $moduleDirs[] = $moduleDir;
                }
            }
        }

        $phpunitXml = simplexml_load_file($phpunitXmlPath);

        $sourceInclude = $phpunitXml->xpath('//source/include')[0];

        unset($sourceInclude->directory);

        $sourceInclude->addChild('directory', './app');

        foreach ($moduleDirs as $dir) {
            $directory = $sourceInclude->addChild('directory', str_replace(base_path(), '.', $dir));
            $directory->addAttribute('suffix', '.php');
        }

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($phpunitXml->asXML());
        $dom->save($phpunitXmlPath);

        $this->info('phpunit.xml updated with enabled module directories.');

        return 0;
    }
}
