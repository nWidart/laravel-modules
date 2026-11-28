<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every generator that writes a class named by its argument must put it where the module's
 * PSR-4 autoloading looks for it, also for a name with folders.
 */
class GeneratorNestedNameTest extends BaseTestCase
{
    /**
     * Generators whose argument does not name a class.
     */
    private const NOT_CLASS_NAMES = [
        'module:make-component-view',
        'module:make-config',
        'module:make-inertia-component',
        'module:make-inertia-page',
        'module:make-migration',
        'module:make-view',
    ];

    /**
     * @var Filesystem
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

    /**
     * @return array<string, array{string}>
     */
    public static function nestedNames(): array
    {
        return [
            'lowercase folders' => ['api/v2/Payments'],
            'folder named like the class' => ['Payments/Payments'],
            'backslash separators' => ['api\\v2\\payments'],
        ];
    }

    #[DataProvider('nestedNames')]
    public function test_every_class_generator_writes_the_class_where_psr4_autoloading_finds_it(string $name)
    {
        $psr4 = $this->psr4Map();
        $generated = 0;

        foreach ($this->classGenerators() as $command => $argument) {
            $before = $this->moduleFiles();

            $code = $this->artisan($command, [$argument => $name, 'module' => 'Blog']);

            $this->assertSame(0, $code, "{$command} {$name}: ".Artisan::output());

            foreach (array_diff($this->moduleFiles(), $before) as $file) {
                $class = $this->declaredClass($file);

                if ($class === null) {
                    continue;
                }

                $this->assertSame($this->psr4Path($psr4, $class), $file, "{$command} {$name} declares {$class}");
                $generated++;
            }
        }

        $this->assertGreaterThanOrEqual(count($this->classGenerators()), $generated);
    }

    public function test_a_nested_factory_and_observer_refer_to_the_model_in_its_folder()
    {
        $this->artisan('module:make-factory', ['name' => 'api/v2/Payment', 'module' => 'Blog']);
        $this->artisan('module:make-observer', ['name' => 'api/v2/Payment', 'module' => 'Blog']);

        $factory = $this->finder->get($this->getModuleBasePath().'/database/factories/Api/V2/PaymentFactory.php');
        $observer = $this->finder->get($this->getModuleAppPath().'/Observers/Api/V2/PaymentObserver.php');

        $this->assertStringContainsString('class PaymentFactory extends Factory', $factory);
        $this->assertStringContainsString('protected $model = \Modules\Blog\Models\Api\V2\Payment::class;', $factory);
        $this->assertStringContainsString('class PaymentObserver', $observer);
        $this->assertStringContainsString('use Modules\Blog\Models\Api\V2\Payment;', $observer);
        $this->assertStringContainsString('public function created(Payment $payment): void {}', $observer);
    }

    /**
     * The generators to run, with the argument that takes the class name.
     *
     * @return array<string, string>
     */
    private function classGenerators(): array
    {
        $generators = [];

        foreach (ConsoleServiceProvider::defaultCommands() as $class) {
            if (! is_subclass_of($class, GeneratorCommand::class)) {
                continue;
            }

            $command = $this->app->make($class);

            if (in_array($command->getName(), self::NOT_CLASS_NAMES, true)) {
                continue;
            }

            foreach ($command->getDefinition()->getArguments() as $argument) {
                if ($argument->isRequired() && $argument->getName() !== 'module') {
                    $generators[$command->getName()] = $argument->getName();
                }
            }
        }

        return $generators;
    }

    /**
     * The PSR-4 prefixes of the module's composer.json, autoload and autoload-dev.
     *
     * @return array<string, string>
     */
    private function psr4Map(): array
    {
        $composer = json_decode($this->finder->get($this->getModuleBasePath().'/composer.json'), true);

        return ($composer['autoload']['psr-4'] ?? []) + ($composer['autoload-dev']['psr-4'] ?? []);
    }

    /**
     * The file PSR-4 autoloading loads the class from, by the longest matching prefix.
     *
     * @param  array<string, string>  $psr4
     */
    private function psr4Path(array $psr4, string $class): ?string
    {
        $matches = array_filter(array_keys($psr4), fn (string $prefix) => str_starts_with($class, $prefix));

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (string $a, string $b) => strlen($b) <=> strlen($a));
        $prefix = $matches[0];

        return $this->getModuleBasePath().'/'.rtrim($psr4[$prefix], '/').'/'
            .str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }

    /**
     * The fully qualified name of the class, interface, trait or enum the file declares, or
     * null for a PHP file without one (such as a Blade view). The file must be valid PHP.
     */
    private function declaredClass(string $file): ?string
    {
        if (! str_ends_with($file, '.php')) {
            return null;
        }

        $code = $this->finder->get($file);

        try {
            \PhpToken::tokenize($code, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $this->fail("{$file} is not valid PHP: {$e->getMessage()}\n{$code}");
        }

        if (preg_match('/^(?:(?:final|abstract|readonly)\s+)*(?:class|interface|trait|enum)\s+(\w+)/m', $code, $class) !== 1) {
            return null;
        }

        $namespace = preg_match('/^namespace\s+([\w\\\\]+);/m', $code, $match) === 1 ? $match[1].'\\' : '';

        return $namespace.$class[1];
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
