<?php

namespace Nwidart\Modules\tests;

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
        if ($this->finder->exists(base_path('my-command.php'))) {
            $this->finder->delete(base_path('my-command.php'));
        }
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
}
