<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SeedMakeCommandTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $seederPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->seederPath = $this->getModuleBasePath().'/database/seeders';
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public static function clashingNames(): array
    {
        return [
            'Seeder' => ['Seeder', 'Seeder.php'],
            'nested Seeder' => ['Admin/Seeder', 'Admin/Seeder.php'],
        ];
    }

    #[DataProvider('clashingNames')]
    public function test_it_rejects_a_name_that_resolves_to_the_seeder_base_class(string $name, string $file)
    {
        $code = $this->artisan('module:make-seed', ['name' => $name, 'module' => 'Blog']);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString("The name [{$name}] resolves to the class [Seeder], which clashes with the Illuminate\\Database\\Seeder base class.", Artisan::output());
        $this->assertFileDoesNotExist($this->seederPath.'/'.$file);
    }

    public static function validNames(): array
    {
        return [
            'without suffix' => ['Post', [], 'PostSeeder'],
            'with suffix' => ['PostSeeder', [], 'PostSeeder'],
            'lowercase seeder' => ['seeder', [], 'SeederSeeder'],
            'master' => ['Blog', ['--master' => true], 'BlogDatabaseSeeder'],
        ];
    }

    #[DataProvider('validNames')]
    public function test_it_keeps_the_class_name_of_valid_names(string $name, array $options, string $class)
    {
        $path = $this->seederPath."/{$class}.php";
        $this->finder->delete($path);

        $code = $this->artisan('module:make-seed', ['name' => $name, 'module' => 'Blog'] + $options);

        $this->assertSame(0, $code);
        $this->assertFileExists($path);
        $this->assertStringContainsString("class {$class} extends Seeder", $this->finder->get($path));
    }
}
