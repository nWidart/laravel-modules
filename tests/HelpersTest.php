<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;

class HelpersTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
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

    public function test_module_path_throws_for_an_unknown_module()
    {
        $this->expectException(ModuleNotFoundException::class);
        $this->expectExceptionMessage('Module [Unknown] does not exist!');

        module_path('Unknown');
    }

    public function test_module_vite_does_not_mutate_the_shared_vite_instance()
    {
        $vite = $this->app->make(Vite::class);
        $originalHotFile = $vite->hotFile();

        $moduleVite = module_vite('build-blog', 'resources/assets/js/app.js', '/some/hot');

        $this->assertNotSame($vite, $moduleVite);
        $this->assertSame($vite, $this->app->make(Vite::class));

        $this->assertSame($originalHotFile, $vite->hotFile());
        $this->assertSame('build', $this->readViteProperty($vite, 'buildDirectory'));
        $this->assertSame([], $this->readViteProperty($vite, 'entryPoints'));

        $this->assertSame('/some/hot', $moduleVite->hotFile());
        $this->assertSame('build-blog', $this->readViteProperty($moduleVite, 'buildDirectory'));
        $this->assertSame(['resources/assets/js/app.js'], $this->readViteProperty($moduleVite, 'entryPoints'));
    }

    private function readViteProperty(Vite $vite, string $property): mixed
    {
        return (new \ReflectionProperty(Vite::class, $property))->getValue($vite);
    }
}
