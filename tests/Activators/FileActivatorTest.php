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
