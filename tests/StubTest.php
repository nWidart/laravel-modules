<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Support\Stub;

class StubTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    public function setUp()
    {
        parent::setUp();
        $this->finder = $this->app['files'];
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->finder->delete([
            base_path('my-command.php'),
            base_path('stub-override-exists.php'),
            base_path('stub-override-not-exists.php'),
        ]);
    }

    /** @test */
    public function it_initialises_a_stub_instance()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $this->assertTrue(str_contains($stub->getPath(), 'src/Commands/stubs/model.stub'));
        $this->assertEquals(['NAME' => 'Name', ], $stub->getReplaces());
    }

    /** @test */
    public function it_sets_new_replaces_array()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->replace(['VENDOR' => 'MyVendor', ]);
        $this->assertEquals(['VENDOR' => 'MyVendor', ], $stub->getReplaces());
    }

    /** @test */
    public function it_stores_stub_to_specific_path()
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->saveTo(base_path(), 'my-command.php');

        $this->assertTrue($this->finder->exists(base_path('my-command.php')));
    }

    /** @test */
    public function it_sets_new_path()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setPath('/new-path/');

        $this->assertTrue(str_contains($stub->getPath(), 'Commands/stubs/new-path/'));
    }

    /** @test */
    public function use_default_stub_if_override_not_exists()
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->setBasePath(__DIR__ . '/stubs');

        $stub->saveTo(base_path(), 'stub-override-not-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-not-exists.php')));
    }

    /** @test */
    public function use_override_stub_if_exists()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setBasePath(__DIR__ . '/stubs');

        $stub->saveTo(base_path(), 'stub-override-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-exists.php')));
        $this->assertEquals('stub-override', $this->finder->get(base_path('stub-override-exists.php')));
    }
}
