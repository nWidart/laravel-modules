<?php

namespace Nwidart\Modules\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;

class UpdatePhpunitCoverage extends Command
{
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
        $appFolder = config('modules.paths.app_folder', 'app/');
        $appFolder = rtrim($appFolder, '/').'/';
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
        $wanted = [];

        foreach ($enabledModules as $module => $status) {
            if ($status && is_dir($moduleDir = $modulesPath.$module.'/'.$appFolder)) {
                $wanted[] = $this->normalizePath($moduleDir);
            }
        }

        // The DOM is only read: the file is edited line by line, so its formatting is kept.
        $xml = (string) file_get_contents($phpunitXmlPath);
        $dom = new DOMDocument;

        if ($xml === '' || ! @$dom->loadXML($xml)) {
            $this->error("Error parsing XML from {$phpunitXmlPath}");

            return 97;
        }

        $xpath = new DOMXPath($dom);
        $existing = [];

        foreach ($xpath->query('/*/source/include/directory') as $directory) {
            $existing[] = $this->normalizePath($directory->textContent);
        }

        // The entries this command writes: the app folder of a module in the modules path.
        $app = trim($appFolder, '/');
        $moduleEntry = '#^'.preg_quote($this->normalizePath($modulesPath), '#').'/[^/]+'.($app === '' ? '' : '/'.preg_quote($app, '#')).'$#';
        $remove = array_filter($existing, fn ($path) => preg_match($moduleEntry, $path) === 1 && ! in_array($path, $wanted, true));
        $add = array_map(
            fn ($path) => '<directory suffix=".php">'.(preg_match('#^([A-Za-z]:)?/#', $path) === 1 ? '' : './').htmlspecialchars($path, ENT_XML1).'/</directory>',
            array_values(array_diff($wanted, $existing))
        );

        $lines = preg_split('/(?<=\n)/', $xml, -1, PREG_SPLIT_NO_EMPTY);
        $eol = str_contains($xml, "\r\n") ? "\r\n" : "\n";
        $unit = preg_match('/^([ \t]+)</m', $xml, $match) === 1 ? $match[1] : '    ';
        $inSource = $inInclude = false;
        $sourceLine = $includeEnd = $siblingIndent = null;

        foreach ($lines as $i => $line) {
            if (preg_match('#^\s*<source\b[^>]*(?<!/)>\s*$#', $line)) {
                [$inSource, $sourceLine] = [true, $i];
            } elseif (str_contains($line, '</source>')) {
                $inSource = false;
            } elseif ($inSource && preg_match('#^\s*<include\b[^>]*(?<!/)>\s*$#', $line)) {
                $inInclude = true;
            } elseif ($inInclude && preg_match('#^\s*</include>\s*$#', $line)) {
                [$inInclude, $includeEnd] = [false, $i];
            } elseif ($inInclude && preg_match('#^(\s*)<directory\b#', $line, $match)) {
                $siblingIndent ??= $match[1];

                // Remove a module entry written on a line of its own.
                if (preg_match('#^\s*<directory\b[^>]*>([^<]*)</directory>\s*$#', $line, $match)) {
                    $path = $this->normalizePath(html_entity_decode($match[1], ENT_QUOTES | ENT_XML1));

                    if (($key = array_search($path, $remove, true)) !== false) {
                        unset($lines[$i], $remove[$key]);
                    }
                }
            }
        }

        foreach ($remove as $path) {
            $this->warn("Could not remove the entry [{$path}] from phpunit.xml: remove it by hand.");
        }

        if ($add !== []) {
            $block = fn (string $indent, array $entries) => implode('', array_map(fn ($entry) => $indent.$entry.$eol, $entries));
            $include = fn (string $indent) => $block($indent, ['<include>'])
                .$block($indent.$unit, ['<directory>./app</directory>', ...$add])
                .$block($indent, ['</include>']);
            $rootEnd = array_key_last(preg_grep('#^\s*</phpunit>\s*$#', $lines) ?: []);

            if ($includeEnd !== null) {
                // Before </include>, indented like the other entries.
                $lines[$includeEnd] = $block($siblingIndent ?? $this->indentOf($lines[$includeEnd]).$unit, $add).$lines[$includeEnd];
            } elseif ($xpath->query('/*/source/include')->length === 0 && $sourceLine !== null) {
                // Right after <source>.
                $lines[$sourceLine] .= $include($this->indentOf($lines[$sourceLine]).$unit);
            } elseif ($xpath->query('/*/source')->length === 0 && $rootEnd !== null) {
                // Before </phpunit>.
                $indent = $this->indentOf($lines[$rootEnd]).$unit;
                $lines[$rootEnd] = $block($indent, ['<source>']).$include($indent.$unit).$block($indent, ['</source>']).$lines[$rootEnd];
            } else {
                $this->warn('Could not find where to add the module entries in phpunit.xml: add them by hand.');
            }
        }

        if (($updated = implode('', $lines)) !== $xml) {
            file_put_contents($phpunitXmlPath, $updated);
        }

        $this->info('phpunit.xml updated with enabled module directories.');

        return 0;
    }

    /**
     * Normalize a path for comparison: forward slashes, relative to the project
     * when inside it, without a leading "./" or trailing slash.
     */
    private function normalizePath(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', trim($path)), '/');
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        if (str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        while (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }

        return $path;
    }

    private function indentOf(string $line): string
    {
        return substr($line, 0, strspn($line, " \t"));
    }
}
