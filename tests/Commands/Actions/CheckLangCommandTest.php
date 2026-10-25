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

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName]]);
        $output = Artisan::output();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Missing language key: fr/messages.php | key: goodbye', $output);
        $this->assertStringNotContainsString('Missing language key: en/messages.php', $output);
    }

    public function test_it_reports_a_language_file_missing_from_a_locale()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome']);
        $this->writeLangFile('en/validation.php', ['required' => 'Required']);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName]]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Missing language file: fr/validation.php', Artisan::output());
    }

    public function test_it_exits_with_success_when_every_locale_has_the_same_files_and_keys()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome', 'nested' => ['title' => 'Title']]);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue', 'nested' => ['title' => 'Titre']]);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName]]);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringNotContainsString('Missing language', $output);
    }

    public function test_it_fails_when_one_of_several_modules_misses_a_key()
    {
        $this->createModule('Shop');

        try {
            $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome']);
            $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);
            $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome', 'cart' => 'Cart'], 'Shop');
            $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue'], 'Shop');

            $code = Artisan::call('module:lang', ['module' => [$this->moduleName, 'Shop']]);

            $this->assertSame(1, $code);
            $this->assertStringContainsString('Shop - Missing language key: fr/messages.php | key: cart', Artisan::output());
        } finally {
            $this->app[RepositoryInterface::class]->delete('Shop');
        }
    }

    private function writeLangFile(string $file, array $values, ?string $module = null): void
    {
        $path = module_path($module ?? $this->moduleName, config('modules.paths.generator.lang.path')).'/'.$file;

        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, '<?php return '.var_export($values, true).';');
    }
}
