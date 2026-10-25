<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class CheckLangCommandTest extends BaseTestCase
{
    /**
     * The module name contains the locale "en", so a replace over the whole path would break it.
     */
    private string $moduleName = 'Content';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule($this->moduleName);
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete($this->moduleName);
        parent::tearDown();
    }

    public function test_it_reports_missing_keys_when_the_locale_also_appears_elsewhere_in_the_path()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome', 'goodbye' => 'Goodbye']);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);

        $this->assertStringContainsString('en', module_path($this->moduleName));

        Artisan::call('module:lang', ['module' => [$this->moduleName]]);
        $output = Artisan::output();

        $this->assertStringContainsString('Missing language key: fr/messages.php | key: goodbye', $output);
        $this->assertStringNotContainsString('Missing language key: en/messages.php', $output);
    }

    public function test_it_reports_a_language_file_missing_from_a_locale()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome']);
        $this->writeLangFile('en/validation.php', ['required' => 'Required']);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);

        Artisan::call('module:lang', ['module' => [$this->moduleName]]);

        $this->assertStringContainsString('Missing language file: fr/validation.php', Artisan::output());
    }

    private function writeLangFile(string $file, array $values): void
    {
        $path = module_path($this->moduleName, config('modules.paths.generator.lang.path')).'/'.$file;

        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, '<?php return '.var_export($values, true).';');
    }
}
