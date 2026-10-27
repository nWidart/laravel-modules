<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ResourceMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_resource_class()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Transformers/PostsTransformer.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostsTransformer.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_collection_resource_class()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog', '--collection' => true]);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostsTransformer.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_json_api_resource_class()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostResource', 'module' => 'Blog', '--json-api' => true]);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostResource.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_generate_a_json_api_resource_class_with_the_shortcut()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostResource', 'module' => 'Blog', '-j' => true]);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostResource.php');

        $this->assertStringContainsString('class PostResource extends JsonApiResource', $file);
        $this->assertSame(0, $code);
    }

    public function test_collection_option_takes_precedence_over_json_api()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostResource', 'module' => 'Blog', '--json-api' => true, '--collection' => true]);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostResource.php');

        $this->assertStringContainsString('class PostResource extends ResourceCollection', $file);
        $this->assertStringNotContainsString('JsonApiResource', $file);
        $this->assertSame(0, $code);
    }

    public function test_collection_name_takes_precedence_over_json_api()
    {
        $code = $this->artisan('module:make-resource', ['name' => 'PostCollection', 'module' => 'Blog', '--json-api' => true]);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostCollection.php');

        $this->assertStringContainsString('class PostCollection extends ResourceCollection', $file);
        $this->assertStringNotContainsString('JsonApiResource', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.resource.path', 'app/Http/Resources');

        $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog', '--collection' => true]);

        $file = $this->finder->get($this->modulePath.'/Http/Resources/PostsTransformer.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.resource.namespace', 'Http\\Resources');

        $code = $this->artisan('module:make-resource', ['name' => 'PostsTransformer', 'module' => 'Blog', '--collection' => true]);

        $file = $this->finder->get($this->modulePath.'/Transformers/PostsTransformer.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
