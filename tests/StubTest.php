<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Stub;

class StubTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->finder->delete([
            base_path('my-command.php'),
            base_path('stub-override-exists.php'),
            base_path('stub-override-not-exists.php'),
        ]);
    }

    public function test_it_initialises_a_stub_instance()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $this->assertTrue(Str::contains($stub->path(), 'src/Commands/stubs/model.stub'));
        $this->assertEquals(['NAME' => 'Name'], $stub->getReplaces());
    }

    public function test_it_sets_new_replaces_array()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->replace(['VENDOR' => 'MyVendor']);
        $this->assertEquals(['VENDOR' => 'MyVendor'], $stub->getReplaces());
    }

    public function test_it_stores_stub_to_specific_path()
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->saveTo(base_path(), 'my-command.php');

        $this->assertTrue($this->finder->exists(base_path('my-command.php')));
    }

    public function test_it_sets_new_path()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setPath('/new-path/');

        $this->assertTrue(Str::contains($stub->path(), 'Commands/stubs/new-path/'));
    }

    public function test_use_default_stub_if_override_not_exists()
    {
        $stub = new Stub('/command.stub', [
            'COMMAND_NAME' => 'my:command',
            'NAMESPACE' => 'Blog\Commands',
            'CLASS' => 'MyCommand',
        ]);

        $stub->setBasePath(__DIR__.'/stubs');

        $stub->saveTo(base_path(), 'stub-override-not-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-not-exists.php')));
    }

    public function test_use_override_stub_if_exists()
    {
        $stub = new Stub('/model.stub', [
            'NAME' => 'Name',
        ]);

        $stub->setBasePath(__DIR__.'/stubs');

        $stub->saveTo(base_path(), 'stub-override-exists.php');

        $this->assertTrue($this->finder->exists(base_path('stub-override-exists.php')));
        $this->assertEquals('stub-override', $this->finder->get(base_path('stub-override-exists.php')));
    }
}
