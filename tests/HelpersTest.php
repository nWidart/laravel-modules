<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\RepositoryInterface;

class HelpersTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_finds_the_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Blog'), 'modules/Blog'));
    }

    public function test_it_can_bind_a_relative_path_to_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Blog', 'config/config.php'), 'modules/Blog/config/config.php'));
    }
}
