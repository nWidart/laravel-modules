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

    public function test_json_lists_missing_files_and_nested_keys_per_locale()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => ['title' => 'Welcome', 'body' => 'Hello'], 'bye' => 'Bye']);
        $this->writeLangFile('en/auth.php', ['failed' => 'Failed']);
        $this->writeLangFile('fr/messages.php', ['bye' => 'Au revoir']);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true]);

        $this->assertSame(1, $code);
        $this->assertSame([
            'modules' => [[
                'name' => $this->moduleName,
                'checked' => true,
                'locales' => [
                    'en' => ['missing_files' => [], 'missing_keys' => []],
                    'fr' => [
                        'missing_files' => ['auth.php'],
                        'missing_keys' => ['messages.php' => ['welcome.title', 'welcome.body']],
                    ],
                ],
            ]],
        ], $this->decodeJson());
    }

    public function test_json_compares_three_locales_and_lists_each_key_once()
    {
        $this->writeLangFile('de/messages.php', ['a' => 'A', 'b' => 'B']);
        $this->writeLangFile('en/messages.php', ['a' => 'A', 'c' => 'C']);
        $this->writeLangFile('fr/messages.php', ['a' => 'A']);
        $this->writeLangFile('fr/extra.php', ['x' => 'X']);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true]);

        $this->assertSame(1, $code);
        $this->assertSame([
            'de' => ['missing_files' => ['extra.php'], 'missing_keys' => ['messages.php' => ['c']]],
            'en' => ['missing_files' => ['extra.php'], 'missing_keys' => ['messages.php' => ['b']]],
            'fr' => ['missing_files' => [], 'missing_keys' => ['messages.php' => ['b', 'c']]],
        ], $this->decodeJson()['modules'][0]['locales']);
    }

    public function test_json_lists_a_single_locale_as_not_checked()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome']);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame([
            'modules' => [[
                'name' => $this->moduleName,
                'checked' => false,
                'locales' => ['en' => ['missing_files' => [], 'missing_keys' => []]],
            ]],
        ], $this->decodeJson());
    }

    public function test_json_lists_a_module_without_lang_folder_with_empty_locales()
    {
        $this->app['files']->deleteDirectory(module_path($this->moduleName, config('modules.paths.generator.lang.path')));

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame('{"modules":[{"name":"Content","checked":false,"locales":{}}]}', trim(Artisan::output()));
    }

    public function test_json_encodes_empty_maps_as_objects()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome']);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true]);

        $this->assertSame(0, $code);
        $this->assertSame(
            '{"modules":[{"name":"Content","checked":true,"locales":{"en":{"missing_files":[],"missing_keys":{}},"fr":{"missing_files":[],"missing_keys":{}}}}]}',
            trim(Artisan::output()),
        );
    }

    public function test_json_lists_every_module_with_all()
    {
        $this->createModule('Shop');

        try {
            $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome']);
            $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);
            $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome', 'cart' => 'Cart'], 'Shop');
            $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue'], 'Shop');

            $code = Artisan::call('module:lang', ['--all' => true, '--json' => true]);
            $modules = array_column($this->decodeJson()['modules'], null, 'name');

            $this->assertSame(1, $code);
            $this->assertEqualsCanonicalizing([$this->moduleName, 'Shop'], array_keys($modules));
            $this->assertSame([], $modules[$this->moduleName]['locales']['fr']['missing_keys']);
            $this->assertSame(['messages.php' => ['cart']], $modules['Shop']['locales']['fr']['missing_keys']);
        } finally {
            $this->app[RepositoryInterface::class]->delete('Shop');
        }
    }

    public function test_json_output_is_only_one_line_of_json()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome', 'goodbye' => 'Goodbye']);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);

        Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString('Checking languages', $output);
        $this->assertStringNotContainsString('Missing language', $output);
        $this->assertCount(1, explode("\n", trim($output)));
        $this->assertStringEndsWith("\n", $output);
    }

    public function test_json_output_has_no_ansi_codes_with_ansi()
    {
        $this->writeLangFile('en/messages.php', ['welcome' => 'Welcome', 'goodbye' => 'Goodbye']);
        $this->writeLangFile('fr/messages.php', ['welcome' => 'Bienvenue']);

        Artisan::call('module:lang', ['module' => [$this->moduleName], '--json' => true, '--ansi' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString("\e[", $output);
        $this->assertSame(['messages.php' => ['goodbye']], json_decode($output, true, flags: JSON_THROW_ON_ERROR)['modules'][0]['locales']['fr']['missing_keys']);
    }

    public function test_text_output_lists_missing_files_then_missing_keys_per_locale()
    {
        $this->writeLangFile('de/messages.php', ['a' => 'A', 'b' => 'B']);
        $this->writeLangFile('en/messages.php', ['a' => 'A', 'c' => 'C']);
        $this->writeLangFile('fr/messages.php', ['a' => 'A']);
        $this->writeLangFile('fr/extra.php', ['x' => 'X']);

        $code = Artisan::call('module:lang', ['module' => [$this->moduleName]]);
        $lines = array_values(array_filter(array_map('trim', explode("\n", Artisan::output()))));

        $this->assertSame(1, $code);
        $this->assertSame([
            'INFO  Checking languages ...',
            'ERROR  Missing language files in de directory.',
            '⇂ Content - Missing language file: de/extra.php',
            'ERROR  Missing language files in en directory.',
            '⇂ Content - Missing language file: en/extra.php',
            'ERROR  Missing language keys for directory en:',
            '⇂ Content - Missing language key: en/messages.php | key: b',
            'ERROR  Missing language keys for directory fr:',
            '⇂ Content - Missing language key: fr/messages.php | key: b',
            '⇂ Content - Missing language key: fr/messages.php | key: c',
            'ERROR  Missing language keys for directory de:',
            '⇂ Content - Missing language key: de/messages.php | key: c',
        ], $lines);
    }

    private function decodeJson(): array
    {
        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function writeLangFile(string $file, array $values, ?string $module = null): void
    {
        $path = module_path($module ?? $this->moduleName, config('modules.paths.generator.lang.path')).'/'.$file;

        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, '<?php return '.var_export($values, true).';');
    }
}
