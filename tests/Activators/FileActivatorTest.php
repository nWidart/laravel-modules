<?php

namespace Nwidart\Modules\Tests\Activators;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Laravel\Module;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;

class FileActivatorTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var TestModule
     */
    private $module;

    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var FileActivator
     */
    private $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new TestModule($this->app, 'Recipe', __DIR__.'/stubs/valid/Recipe');
        $this->finder = $this->app['files'];
        $this->activator = new FileActivator($this->app);
    }

    protected function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    public function test_it_creates_valid_json_file_after_enabling()
    {
        $this->activator->enable($this->module);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->activator->setActive($this->module, true);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }

    public function test_it_creates_valid_json_file_after_disabling()
    {
        $this->activator->disable($this->module);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->activator->setActive($this->module, false);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }

    public function test_it_can_check_module_enabled_status()
    {
        $this->activator->enable($this->module);
        $this->assertTrue($this->activator->hasStatus($this->module, true));

        $this->activator->setActive($this->module, true);
        $this->assertTrue($this->activator->hasStatus($this->module, true));
    }

    public function test_it_can_check_module_disabled_status()
    {
        $this->activator->disable($this->module);
        $this->assertTrue($this->activator->hasStatus($this->module, false));

        $this->activator->setActive($this->module, false);
        $this->assertTrue($this->activator->hasStatus($this->module, false));
    }

    public function test_it_can_check_status_of_module_that_hasnt_been_enabled_or_disabled()
    {
        $this->assertTrue($this->activator->hasStatus($this->module, false));
    }

    public function test_it_throws_a_clear_exception_for_a_corrupt_statuses_file_and_keeps_it()
    {
        $path = $this->activator->getStatusesFilePath();
        $this->finder->put($path, '{"Recipe": true,');

        try {
            new FileActivator($this->app);
            $this->fail('A corrupt statuses file must not be read as empty.');
        } catch (InvalidJsonException $exception) {
            $this->assertStringContainsString("The module statuses file [{$path}] is not valid: Syntax error.", $exception->getMessage());
        }

        $this->assertSame('{"Recipe": true,', $this->finder->get($path));
    }

    public function test_it_throws_a_clear_exception_when_the_statuses_file_is_not_a_json_object()
    {
        $path = $this->activator->getStatusesFilePath();
        $this->finder->put($path, '"Recipe"');

        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage("The module statuses file [{$path}] is not valid: Expected a JSON object.");

        new FileActivator($this->app);
    }

    public function test_it_throws_a_clear_exception_when_the_statuses_file_is_a_json_list()
    {
        $path = $this->activator->getStatusesFilePath();
        $this->finder->put($path, '[1,2]');

        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage("The module statuses file [{$path}] is not valid: Expected a JSON object.");

        new FileActivator($this->app);
    }

    public function test_it_finds_a_status_stored_under_a_different_case()
    {
        $activator = $this->activatorWithStatuses(['Recipe' => true, 'Article' => false]);
        $lowercaseRecipe = new TestModule($this->app, 'recipe', __DIR__.'/stubs/valid/Recipe');
        $lowercaseArticle = new TestModule($this->app, 'article', __DIR__.'/stubs/valid/Recipe');

        $this->assertTrue($activator->hasStatus($lowercaseRecipe, true));
        $this->assertFalse($activator->hasStatus($lowercaseRecipe, false));
        $this->assertTrue($activator->hasStatus('RECIPE', true));
        $this->assertFalse($activator->hasStatus('RECIPE', false));

        $this->assertTrue($activator->hasStatus($lowercaseArticle, false));
        $this->assertFalse($activator->hasStatus($lowercaseArticle, true));
        $this->assertTrue($activator->hasStatus('ARTICLE', false));
        $this->assertFalse($activator->hasStatus('ARTICLE', true));
    }

    public function test_it_updates_the_existing_key_when_enabling_or_disabling_with_a_different_case()
    {
        $activator = $this->activatorWithStatuses(['Article' => true, 'Recipe' => false, 'Zebra' => true]);
        $lowercaseModule = new TestModule($this->app, 'recipe', __DIR__.'/stubs/valid/Recipe');

        $activator->enable($lowercaseModule);

        $this->assertSame(['Article' => true, 'Recipe' => true, 'Zebra' => true], $activator->getStatuses());
        $this->assertSame(['Article' => true, 'Recipe' => true, 'Zebra' => true], $this->storedStatuses());

        $activator->disable($lowercaseModule);

        $this->assertSame(['Article' => true, 'Recipe' => false, 'Zebra' => true], $this->storedStatuses());

        $activator->setActiveByName('RECIPE', true);

        $this->assertSame(['Article' => true, 'Recipe' => true, 'Zebra' => true], $this->storedStatuses());
    }

    public function test_it_collapses_case_variant_duplicates_into_the_first_key_on_write()
    {
        $activator = $this->activatorWithStatuses(['Recipe' => true, 'Article' => true, 'RECIPE' => false]);

        $activator->setActiveByName('recipe', false);

        $this->assertSame(['Recipe' => false, 'Article' => true], $this->storedStatuses());
        $this->assertTrue($activator->hasStatus('recipe', false));
    }

    public function test_it_prefers_an_exact_match_over_case_variants()
    {
        $activator = $this->activatorWithStatuses(['Recipe' => true, 'recipe' => false]);

        $this->assertTrue($activator->hasStatus('recipe', false));
        $this->assertTrue($activator->hasStatus('Recipe', true));

        $activator->setActiveByName('recipe', true);

        $this->assertSame(['recipe' => true], $this->storedStatuses());
    }

    public function test_it_deletes_every_case_variant_of_a_module()
    {
        $activator = $this->activatorWithStatuses(['recipe' => true, 'Article' => true, 'RECIPE' => false]);

        $activator->delete($this->module);

        $this->assertSame(['Article' => true], $activator->getStatuses());
        $this->assertSame(['Article' => true], $this->storedStatuses());

        // Nothing left to remove: the file is not written again.
        $path = $this->activator->getStatusesFilePath();
        $this->finder->put($path, '{"Article":true}');

        $activator->delete($this->module);

        $this->assertSame('{"Article":true}', $this->finder->get($path));
    }

    /**
     * @param  array<string, bool>  $statuses
     */
    private function activatorWithStatuses(array $statuses): FileActivator
    {
        $this->finder->put($this->activator->getStatusesFilePath(), json_encode($statuses, JSON_PRETTY_PRINT));

        return new FileActivator($this->app);
    }

    /**
     * @return array<string, bool>
     */
    private function storedStatuses(): array
    {
        return json_decode($this->finder->get($this->activator->getStatusesFilePath()), true);
    }

    #[DataProvider('emptyStatusesFiles')]
    public function test_it_reads_an_empty_statuses_file_as_no_statuses(string $contents)
    {
        $this->finder->put($this->activator->getStatusesFilePath(), $contents);

        $activator = new FileActivator($this->app);

        $this->assertTrue($activator->hasStatus($this->module, false));
    }

    public static function emptyStatusesFiles(): array
    {
        return [
            'empty file' => [''],
            'whitespace' => ["\n "],
            'empty list' => ['[]'],
            'empty object' => ['{}'],
        ];
    }
}

class TestModule extends Module
{
    public function registerProviders(): void
    {
        parent::registerProviders();
    }
}
