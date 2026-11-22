<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
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
            'migration parent segments' => ['module:make-migration', 'name', '../../../../Escaped/create_pwned_table'],
            'health indicator parent segments' => ['module:make-health-indicator', 'name', '../../../../Escaped/Pwned'],
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

    public static function emptyNames(): array
    {
        $cases = [];

        foreach (['empty' => '', 'whitespace only' => "  \t"] as $label => $name) {
            $cases["seed, {$label}"] = ['module:make-seed', 'name', $name];
            $cases["request, {$label}"] = ['module:make-request', 'name', $name];
            $cases["controller, {$label}"] = ['module:make-controller', 'controller', $name];
            $cases["model, {$label}"] = ['module:make-model', 'model', $name];
            $cases["migration, {$label}"] = ['module:make-migration', 'name', $name];
            $cases["component, {$label}"] = ['module:make-component', 'name', $name];
            $cases["health indicator, {$label}"] = ['module:make-health-indicator', 'name', $name];
        }

        return $cases;
    }

    #[DataProvider('emptyNames')]
    public function test_it_rejects_an_empty_name(string $command, string $argument, string $name)
    {
        $before = $this->moduleFiles();

        $code = $this->artisan($command, [$argument => $name, 'module' => 'Blog']);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString("The {$argument} argument must not be empty.", Artisan::output());
        $this->assertSame($before, $this->moduleFiles());
    }

    public function test_it_rejects_an_empty_module_argument_for_providers_named_by_module()
    {
        $before = $this->moduleFiles();

        $code = $this->artisan('module:route-provider', ['module' => '']);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('The module argument must not be empty.', Artisan::output());
        $this->assertSame($before, $this->moduleFiles());
    }

    public function test_providers_named_by_module_still_use_the_current_module_when_none_is_given()
    {
        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $this->app[RepositoryInterface::class]->setUsed('Blog');

        try {
            $code = $this->artisan('module:route-provider');
        } finally {
            $this->app[RepositoryInterface::class]->forgetUsed();
        }

        $this->assertSame(0, $code);
        $this->assertFileExists($path);
    }

    public function test_every_generator_prints_an_error_line_for_an_unknown_module()
    {
        foreach ($this->generators() as $class) {
            $command = $this->app->make($class);

            $code = $this->artisan($command->getName(), $this->requiredArguments($command) + ['module' => 'Nope']);

            $output = Artisan::output();
            $this->assertSame(E_ERROR, $code, "{$command->getName()} should fail for an unknown module.");
            $this->assertStringContainsString('ERROR  Module [Nope] does not exist.', $output, $command->getName());
            $this->assertSame(1, substr_count($output, 'ERROR'), $command->getName());
            $this->assertDirectoryDoesNotExist(base_path('modules/Nope'), $command->getName());
        }
    }

    public function test_every_generator_with_an_optional_module_explains_a_missing_module()
    {
        $this->app[RepositoryInterface::class]->forgetUsed();
        $before = $this->moduleFiles();

        foreach ($this->generators() as $class) {
            $command = $this->app->make($class);

            if ($command->getDefinition()->getArgument('module')->isRequired()) {
                continue;
            }

            $code = $this->artisan($command->getName(), $this->requiredArguments($command));

            $output = Artisan::output();
            $this->assertSame(E_ERROR, $code, "{$command->getName()} should fail without a module.");
            $this->assertStringContainsString(
                'No module given, and no module is set with module:use. Pass the module name, or run php artisan module:use <module> first.',
                $output,
                $command->getName()
            );
            $this->assertStringNotContainsString('Module [] does not exist', $output, $command->getName());
        }

        $this->assertSame($before, $this->moduleFiles());
    }

    public function test_a_generator_explains_a_module_set_with_use_that_no_longer_exists()
    {
        $this->createModule('Gone');
        $this->app[RepositoryInterface::class]->setUsed('Gone');
        $this->app[RepositoryInterface::class]->delete('Gone');

        try {
            $code = $this->artisan('module:make-controller', ['controller' => 'PostController']);
        } finally {
            $this->app[RepositoryInterface::class]->forgetUsed();
        }

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('no module is set with module:use', Artisan::output());
    }

    public function test_it_accepts_names_with_dots_that_are_not_parent_segments()
    {
        $code = $this->artisan('module:make-view', ['name' => 'posts.index', 'module' => 'Blog']);

        $this->assertSame(0, $code);
        $this->assertFileExists($this->getModuleBasePath().'/resources/views/posts.index.blade.php');
    }

    /**
     * @return Collection<int, class-string<GeneratorCommand>>
     */
    private function generators()
    {
        return ConsoleServiceProvider::defaultCommands()
            ->filter(fn (string $class) => is_subclass_of($class, GeneratorCommand::class))
            ->values();
    }

    /**
     * A value for every required argument of the command except the module.
     *
     * @return array<string, string>
     */
    private function requiredArguments(GeneratorCommand $command): array
    {
        $arguments = [];

        foreach ($command->getDefinition()->getArguments() as $argument) {
            if ($argument->isRequired() && $argument->getName() !== 'module') {
                $arguments[$argument->getName()] = 'Post';
            }
        }

        return $arguments;
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
