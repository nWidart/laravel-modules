<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GeneratorCommandTest extends BaseTestCase
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
        $this->finder->deleteDirectory(base_path('Escaped'));
        $this->finder->deleteDirectory(base_path('modules/Escaped'));
        parent::tearDown();
    }

    public function test_every_generator_has_a_force_option()
    {
        $generators = ConsoleServiceProvider::defaultCommands()
            ->filter(fn (string $class) => is_subclass_of($class, GeneratorCommand::class));

        $this->assertNotEmpty($generators);

        foreach ($generators as $class) {
            $definition = $this->app->make($class)->getDefinition();

            $this->assertTrue($definition->hasOption('force'), "{$class} has no --force option.");
            $this->assertFalse($definition->getOption('force')->acceptValue(), "{$class} --force must not take a value.");
        }
    }

    public function test_every_generator_describes_its_force_option()
    {
        $generators = ConsoleServiceProvider::defaultCommands()
            ->filter(fn (string $class) => is_subclass_of($class, GeneratorCommand::class));

        foreach ($generators as $class) {
            $description = $this->app->make($class)->getDefinition()->getOption('force')->getDescription();

            // A sentence of at least four words, not a placeholder like "su.".
            $this->assertMatchesRegularExpression('/^\p{Lu}\w*( \w+){3,}/u', $description, "{$class} --force has no real description.");
        }
    }

    public function test_force_shortcut_does_not_replace_an_existing_f_shortcut()
    {
        $definition = Artisan::all()['module:make-model']->getDefinition();

        $this->assertSame('factory', $definition->getOptionForShortcut('f')->getName());
        $this->assertTrue($definition->hasOption('force'));
        $this->assertNull($definition->getOption('force')->getShortcut());
    }

    public function test_generator_without_own_force_option_gets_f_shortcut()
    {
        $definition = Artisan::all()['module:make-request']->getDefinition();

        $this->assertSame('force', $definition->getOptionForShortcut('f')->getName());
    }

    public function test_it_keeps_an_existing_file_without_force()
    {
        $path = $this->modulePath.'/Http/Requests/CreateBlogPostRequest.php';

        $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $this->assertStringContainsString('already exists', Artisan::output());
        $this->assertSame(E_ERROR, $code);
        $this->assertSame('edited', $this->finder->get($path));
    }

    public function test_it_overwrites_an_existing_file_with_force()
    {
        $path = $this->modulePath.'/Http/Requests/CreateBlogPostRequest.php';

        $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog', '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('class CreateBlogPostRequest', $this->finder->get($path));
    }

    public function test_model_force_overwrites_the_model_without_creating_a_factory()
    {
        $path = $this->modulePath.'/Models/Post.php';

        $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog']);
        $this->finder->put($path, 'edited');

        $code = $this->artisan('module:make-model', ['model' => 'Post', 'module' => 'Blog', '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('class Post', $this->finder->get($path));
        $this->assertFileDoesNotExist($this->getModuleBasePath().'/database/factories/PostFactory.php');
    }

    public function test_component_force_overwrites_the_class_and_the_view()
    {
        $classPath = $this->modulePath.'/View/Components/Alert.php';
        $viewPath = $this->getModuleBasePath().'/resources/views/components/alert.blade.php';

        $this->artisan('module:make-component', ['name' => 'Alert', 'module' => 'Blog']);
        $this->finder->put($classPath, 'edited');
        $this->finder->put($viewPath, 'edited');

        $code = $this->artisan('module:make-component', ['name' => 'Alert', 'module' => 'Blog', '--force' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('class Alert', $this->finder->get($classPath));
        $this->assertStringContainsString('<div>', $this->finder->get($viewPath));
    }

    public static function unsafeNames(): array
    {
        return [
            'parent segments' => ['module:make-controller', 'controller', '../../../../Escaped/Pwned'],
            'parent segment in the middle' => ['module:make-model', 'model', 'Sub/../../../../../Escaped/Pwned'],
            'backslash parent segments' => ['module:make-request', 'name', '..\\..\\..\\..\\Escaped\\Pwned'],
            'trailing parent segment' => ['module:make-view', 'name', 'Escaped/..'],
            'absolute unix path' => ['module:make-policy', 'name', '/tmp/Escaped/Pwned'],
            'absolute windows path' => ['module:make-job', 'name', 'C:\\Escaped\\Pwned'],
        ];
    }

    #[DataProvider('unsafeNames')]
    public function test_it_rejects_names_that_escape_the_module(string $command, string $argument, string $name)
    {
        $before = $this->moduleFiles();

        $code = $this->artisan($command, [$argument => $name, 'module' => 'Blog']);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('must not be an absolute path or contain ".." segments', Artisan::output());
        $this->assertSame($before, $this->moduleFiles());
        $this->assertDirectoryDoesNotExist(base_path('Escaped'));
        $this->assertDirectoryDoesNotExist(base_path('modules/Escaped'));
    }

    public function test_it_accepts_names_with_dots_that_are_not_parent_segments()
    {
        $code = $this->artisan('module:make-view', ['name' => 'posts.index', 'module' => 'Blog']);

        $this->assertSame(0, $code);
        $this->assertFileExists($this->getModuleBasePath().'/resources/views/posts.index.blade.php');
    }

    /**
     * @return list<string>
     */
    private function moduleFiles(): array
    {
        return array_map(
            fn ($file) => $file->getPathname(),
            $this->finder->allFiles($this->getModuleBasePath())
        );
    }
}
