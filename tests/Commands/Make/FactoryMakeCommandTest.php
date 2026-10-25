<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class FactoryMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

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

    public function test_it_makes_factory()
    {
        $code = $this->artisan('module:make-factory', ['name' => 'Post', 'module' => 'Blog']);

        $factoryFile = $this->getModuleBasePath().'/database/factories/PostFactory.php';

        $this->assertTrue(is_file($factoryFile), 'Factory file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($factoryFile));
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_correct_namespace_with_a_custom_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'domain/');
        $this->app['config']->set('modules.paths.generator.model.path', 'domain/Models');

        $code = $this->artisan('module:make-factory', ['name' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/database/factories/PostFactory.php');

        $this->assertStringContainsString('namespace Modules\Blog\Database\Factories;', $file);
        $this->assertStringContainsString('Modules\Blog\Models\Post', $file);
        $this->assertSame(0, $code);
    }
}
