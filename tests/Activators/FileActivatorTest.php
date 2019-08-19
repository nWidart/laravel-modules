<?php

namespace Nwidart\Modules\Tests\Activators;

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class FileActivatorTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var TestModule
     */
    private $module;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    /**
     * @var FileActivator
     */
    private $activator;

    public function setUp(): void
    {
        parent::setUp();
        $this->module = new TestModule($this->app, 'Recipe', __DIR__ . '/stubs/valid/Recipe');
        $this->finder = $this->app['files'];
        $this->activator = new FileActivator($this->app);
    }

    public function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_valid_json_file_after_enabling()
    {
        $this->activator->enable($this->module);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->activator->setActive($this->module, true);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }

    /** @test */
    public function it_creates_valid_json_file_after_disabling()
    {
        $this->activator->disable($this->module);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->activator->setActive($this->module, false);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }

    /** @test */
    public function it_can_check_module_enabled_status()
    {
        $this->activator->enable($this->module);
        $this->assertTrue($this->activator->hasStatus($this->module, true));

        $this->activator->setActive($this->module, true);
        $this->assertTrue($this->activator->hasStatus($this->module, true));
    }

    /** @test */
    public function it_can_check_module_disabled_status()
    {
        $this->activator->disable($this->module);
        $this->assertTrue($this->activator->hasStatus($this->module, false));

        $this->activator->setActive($this->module, false);
        $this->assertTrue($this->activator->hasStatus($this->module, false));
    }

    /** @test */
    public function it_can_check_status_of_module_that_hasnt_been_enabled_or_disabled()
    {
        $this->assertTrue($this->activator->hasStatus($this->module, false));
    }
}

class TestModule extends \Nwidart\Modules\Laravel\Module
{
    public function registerProviders()
    {
        parent::registerProviders();
    }
}
