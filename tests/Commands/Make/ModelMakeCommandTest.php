<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ModelMakeCommandTest extends BaseTestCase
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

    /**
     * @var callable|null
     */
    private $autoloader;

    protected function tearDown(): void
    {
        if ($this->autoloader) {
            spl_autoload_unregister($this->autoloader);
        }

        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_model_class()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Models/Post.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_fillable_fields()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--fillable' => 'title,slug']);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_migration_file_with_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--migration' => true]);

        $migrations = $this->finder->allFiles($this->getModuleBasePath().'/database/migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->getModuleBasePath().'/database/migrations/'.$migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_migration_file_with_model_using_shortcut_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->getModuleBasePath().'/database/migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->getModuleBasePath().'/database/migrations/'.$migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_controller_file_with_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--controller' => true]);
        $controllers = $this->finder->allFiles($this->modulePath.'/Http/Controllers');
        $controllerFile = $controllers[1];
        $controllerContent = $this->finder->get($this->modulePath.'/Http/Controllers/'.$controllerFile->getFilename());
        $this->assertCount(2, $controllers);
        $this->assertMatchesSnapshot($controllerContent);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_controller_file_with_model_using_shortcut_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true]);

        $controllers = $this->finder->allFiles($this->modulePath.'/Http/Controllers');
        $controllerFile = $controllers[1];
        $controllerContent = $this->finder->get($this->modulePath.'/Http/Controllers/'.$controllerFile->getFilename());
        $this->assertCount(2, $controllers);
        $this->assertMatchesSnapshot($controllerContent);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_controller_and_migration_when_both_flags_are_present()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true, '-m' => true]);

        $controllers = $this->finder->allFiles($this->modulePath.'/Http/Controllers');
        $controllerFile = $controllers[1];
        $controllerContent = $this->finder->get($this->modulePath.'/Http/Controllers/'.$controllerFile->getFilename());
        $this->assertCount(2, $controllers);
        $this->assertMatchesSnapshot($controllerContent);

        $migrations = $this->finder->allFiles($this->getModuleBasePath().'/database/migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->getModuleBasePath().'/database/migrations/'.$migrationFile->getFilename());
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);

        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_migration_file_name_with_multiple_words_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'ProductDetail', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->getModuleBasePath().'/database/migrations');
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->getModuleBasePath().'/database/migrations/'.$migrationFile->getFilename());

        $this->assertStringContainsString('create_product_details_table', $migrationFile->getFilename());
        $this->assertMatchesSnapshot($migrationContent);
        $this->assertSame(0, $code);
    }

    public function test_it_displays_error_if_model_already_exists()
    {
        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertStringContainsString('already exists', Artisan::output());
        $this->assertSame(E_ERROR, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.model.path', 'Models');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/Models/Post.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.model.namespace', 'Models');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_policy_file_with_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--policy' => true]);

        $policy = $this->finder->get($this->modulePath.'/Policies/PostPolicy.php');

        $this->assertStringContainsString('namespace Modules\\Blog\\Policies;', $policy);
        $this->assertStringContainsString('class PostPolicy', $policy);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_policy_file_with_all_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true]);

        $this->assertFileExists($this->modulePath.'/Policies/PostPolicy.php');
        $this->assertSame(0, $code);
    }

    public function test_it_does_not_generate_policy_file_without_policy_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertFileDoesNotExist($this->modulePath.'/Policies/PostPolicy.php');
        $this->assertSame(0, $code);
    }

    public function test_it_does_not_add_factory_code_without_factory_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertStringNotContainsString('Factory', $file);
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_generated_factory_with_factory_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--factory' => true]);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertFileExists($this->getModuleBasePath().'/database/factories/PostFactory.php');
        $this->assertStringContainsString('use Modules\\Blog\\Database\\Factories\\PostFactory;', $file);
        $this->assertStringContainsString('#[UseFactory(PostFactory::class)]', $file);
        $this->assertStringContainsString('/** @use HasFactory<PostFactory> */', $file);
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_generated_factory_with_all_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--all' => true]);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertStringContainsString('#[UseFactory(PostFactory::class)]', $file);
        $this->assertSame($this->generatedFactoryClass('PostFactory.php'), $this->factoryClassUsedBy($file));
        $this->assertSame(0, $code);
    }

    public function test_generated_model_resolves_its_generated_factory()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Article', 'module' => 'Blog', '--factory' => true]);

        $this->registerModuleAutoloader();

        $model = 'Modules\\Blog\\Models\\Article';
        $factory = 'Modules\\Blog\\Database\\Factories\\ArticleFactory';

        $this->assertInstanceOf($factory, $model::factory());
        $this->assertInstanceOf($model, $model::factory()->make());
        $this->assertSame(0, $code);
    }

    public function test_factory_class_matches_the_generated_factory_for_a_studly_model_name()
    {
        $code = $this->artisan('module:make-model', ['model' => 'blog_post', 'module' => 'Blog', '--factory' => true]);

        $file = $this->finder->get($this->modulePath.'/Models/BlogPost.php');

        $this->assertSame('Modules\\Blog\\Database\\Factories\\BlogPostFactory', $this->factoryClassUsedBy($file));
        $this->assertSame($this->generatedFactoryClass('BlogPostFactory.php'), $this->factoryClassUsedBy($file));
        $this->assertSame(0, $code);
    }

    public function test_factory_class_follows_the_generated_factory_namespace_for_a_nested_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Admin/Post', 'module' => 'Blog', '--factory' => true]);

        $file = $this->finder->get($this->modulePath.'/Models/Admin/Post.php');
        $factory = $this->finder->get($this->getModuleBasePath().'/database/factories/Admin/PostFactory.php');

        $this->assertSame('Modules\\Blog\\Database\\Factories\\Admin\\PostFactory', $this->factoryClassUsedBy($file));
        $this->assertStringContainsString('namespace Modules\\Blog\\Database\\Factories\\Admin;', $factory);
        $this->assertSame(0, $code);
    }

    public function test_factory_class_follows_a_custom_module_namespace_and_factory_path()
    {
        $this->app['config']->set('modules.namespace', 'App\\Modules');
        $this->app['config']->set('modules.paths.generator.factory.path', 'database/model_factories');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--factory' => true]);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertSame('App\\Modules\\Blog\\Database\\ModelFactories\\PostFactory', $this->factoryClassUsedBy($file));
        $this->assertSame(
            $this->generatedFactoryClass('PostFactory.php', 'database/model_factories'),
            $this->factoryClassUsedBy($file),
        );
        $this->assertSame(0, $code);
    }

    public function test_factory_class_follows_a_custom_factory_namespace()
    {
        $this->app['config']->set('modules.paths.generator.factory.namespace', 'Factories');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--factory' => true]);

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertSame('Modules\\Blog\\Factories\\PostFactory', $this->factoryClassUsedBy($file));
        $this->assertSame($this->generatedFactoryClass('PostFactory.php'), $this->factoryClassUsedBy($file));
        $this->assertSame(0, $code);
    }

    public function test_a_published_model_stub_without_factory_placeholders_keeps_its_output()
    {
        $stubs = sys_get_temp_dir().'/laravel-modules-old-model-stub-'.getmypid();
        $this->finder->ensureDirectoryExists($stubs);
        $this->finder->put($stubs.'/model.stub', <<<'STUB'
<?php

namespace $NAMESPACE$;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use $MODULE_NAMESPACE$\$MODULE$\Database\Factories\$NAME$Factory;

class $CLASS$ extends Model
{
    use HasFactory;
}

STUB);

        $basePath = Stub::getBasePath();
        Stub::setBasePath($stubs);

        try {
            $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--factory' => true]);
        } finally {
            Stub::setBasePath((string) $basePath);
            $this->finder->deleteDirectory($stubs);
        }

        $file = $this->finder->get($this->modulePath.'/Models/Post.php');

        $this->assertStringContainsString('// use Modules\\Blog\\Database\\Factories\\PostFactory;', $file);
        $this->assertStringNotContainsString('UseFactory', $file);
        $this->assertStringNotContainsString('$FACTORY', $file);
        $this->assertSame(0, $code);
    }

    /**
     * Get the factory class named in the model's #[UseFactory] attribute, resolved through its use statements.
     */
    private function factoryClassUsedBy(string $model): string
    {
        $this->assertSame(1, preg_match('/^#\[UseFactory\((\w+)::class\)\]$/m', $model, $attribute));
        $this->assertSame(1, preg_match('/^use ([\w\\\\]+\\\\'.$attribute[1].');$/m', $model, $import));

        return $import[1];
    }

    /**
     * Get the class that module:make-factory wrote to the given file, from its namespace and class declarations.
     */
    private function generatedFactoryClass(string $file, string $path = 'database/factories'): string
    {
        $factory = $this->finder->get($this->getModuleBasePath().'/'.$path.'/'.$file);

        $this->assertSame(1, preg_match('/^namespace ([\w\\\\]+);$/m', $factory, $namespace));
        $this->assertSame(1, preg_match('/^class (\w+) extends Factory$/m', $factory, $class));

        return $namespace[1].'\\'.$class[1];
    }

    /**
     * Autoload the module's classes with the PSR-4 map of its generated composer.json, as an app would.
     */
    private function registerModuleAutoloader(): void
    {
        $base = $this->getModuleBasePath();
        $psr4 = json_decode($this->finder->get($base.'/composer.json'), true)['autoload']['psr-4'];

        $this->autoloader = function (string $class) use ($base, $psr4) {
            // Longest prefix first, so Database\Factories wins over the module root.
            uksort($psr4, fn ($a, $b) => strlen($b) <=> strlen($a));

            foreach ($psr4 as $prefix => $directory) {
                if (! str_starts_with($class, $prefix)) {
                    continue;
                }

                $file = $base.'/'.rtrim($directory, '/').'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

                if (is_file($file)) {
                    require $file;

                    return;
                }
            }
        };

        spl_autoload_register($this->autoloader);
    }
}
