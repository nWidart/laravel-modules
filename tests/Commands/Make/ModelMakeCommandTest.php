<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ModelMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_generates_new_model_class()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Models/Post.php')));
        $this->assertSame(0, $code);
    }

    public function test_generates_correct_file_with_content()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Models/Post.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_generates_correct_fillable_fields()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--fillable' => 'title,slug']);

        $file = $this->finder->get($this->module_app_path('app/Models/Post.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_migration_file_with_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--migration' => true]);

        $migrations = $this->finder->allFiles($this->module_path('database/migrations'));
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->module_path("database/migrations/{$migrationFile->getFilename()}"));
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_migration_file_with_model_using_shortcut_option()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->module_path('database/migrations'));
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->module_path("database/migrations/{$migrationFile->getFilename()}"));
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);
        $this->assertSame(0, $code);
    }

    public function test_generates_controller()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--controller' => true]);
        $controllers = $this->finder->allFiles($this->module_app_path('app/Http/Controllers'));
        $controllerFile = $controllers[1];
        $controllerContent = $this->finder->get($this->module_app_path("Http/Controllers/{$controllerFile->getFilename()}"));
        $this->assertCount(2, $controllers);
        $this->assertMatchesSnapshot($controllerContent);
        $this->assertSame(0, $code);
    }

    public function test_generates_controller_when_flag_is_present()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true]);

        $controllers = $this->finder->allFiles($this->module_app_path('app/Http/Controllers'));
        $controllerFile = $controllers[1];
        $controllerContent = $this->finder->get($this->module_app_path("Http/Controllers/{$controllerFile->getFilename()}"));
        $this->assertCount(2, $controllers);
        $this->assertMatchesSnapshot($controllerContent);
        $this->assertSame(0, $code);
    }

    public function test_generates_controller_and_migration_when_flags_are_present()
    {
        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '-c' => true, '-m' => true]);

        $controllers = $this->finder->allFiles($this->module_app_path('app/Http/Controllers'));
        $controllerFile = $controllers[1];
        $controllerContent = $this->finder->get($this->module_app_path("Http/Controllers/{$controllerFile->getFilename()}"));
        $this->assertCount(2, $controllers);
        $this->assertMatchesSnapshot($controllerContent);

        $migrations = $this->finder->allFiles($this->module_path('database/migrations'));
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->module_path("database/migrations/{$migrationFile->getFilename()}"));
        $this->assertCount(1, $migrations);
        $this->assertMatchesSnapshot($migrationContent);

        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_migration_file_name_with_multiple_words_model()
    {
        $code = $this->artisan('module:make-model', ['model' => 'ProductDetail', 'module' => 'Blog', '-m' => true]);

        $migrations = $this->finder->allFiles($this->module_path('database/migrations'));
        $migrationFile = $migrations[0];
        $migrationContent = $this->finder->get($this->module_path("database/migrations/{$migrationFile->getFilename()}"));

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

    public function test_changes_default_path()
    {
        $this->app['config']->set('modules.paths.generator.model.path', 'Models');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_path('Models/Post.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_changes_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.model.namespace', 'Models');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Models/Post.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
