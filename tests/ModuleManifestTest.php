<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\ModuleManifest;

class ModuleManifestTest extends BaseTestCase
{
    private ActivatorInterface $activator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->activator->reset();

        parent::tearDown();
    }

    public function test_a_boot_finds_and_reads_each_module_json_once()
    {
        $files = new class extends Filesystem
        {
            public array $globs = [];

            public array $reads = [];

            public function glob($pattern, $flags = 0)
            {
                $this->globs[] = $pattern;

                return parent::glob($pattern, $flags);
            }

            public function get($path, $lock = false)
            {
                $this->reads[] = $path;

                return parent::get($path, $lock);
            }
        };
        $this->app->instance('files', $files);
        $repository = $this->bindRepository(__DIR__.'/stubs/valid');
        $this->activator->setActiveByName('Recipe', true);
        $manifest = $this->makeManifest($files, $repository->getScanPaths());

        // What a boot does: the package provider loads the manifest, then each module provider calls module_path().
        $manifest->getAliases();
        $manifest->getProviders();
        $manifest->registerFiles();
        foreach (range(1, 5) as $call) {
            module_path('Recipe', 'config');
            module_path('Requirement');
        }
        $repository->find('Recipe')->get('priority');
        $repository->getOrdered();

        $moduleJsonReads = array_values(array_filter($files->reads, fn ($path) => str_ends_with($path, 'module.json')));
        $this->assertSame([
            __DIR__.'/stubs/valid/Recipe/module.json',
            __DIR__.'/stubs/valid/Requirement/module.json',
        ], $moduleJsonReads);
        $this->assertSame(array_values(array_unique($files->globs)), $files->globs);
        $this->assertSame(['recipe'], array_keys($repository->allEnabled()));
        $this->assertSame(['Recipe'], $manifest->getModulesData()->pluck('name')->all());
    }

    public function test_it_keeps_its_data_until_the_repository_scans_again()
    {
        $location = $this->makeLocation(['Blog' => ['name' => 'Blog', 'providers' => ['BlogProvider']]]);
        $repository = $this->bindRepository($location);
        $this->activator->setActiveByName('Blog', true);
        $manifest = $this->makeManifest(new Filesystem, $repository->getScanPaths());

        $this->assertSame(['BlogProvider'], $manifest->getProviders());

        $this->app['files']->put($location.'/Blog/module.json', json_encode(['name' => 'Blog', 'providers' => ['NewBlogProvider']]));

        $this->assertSame(['BlogProvider'], $manifest->getModulesData()->pluck('providers')->flatten()->all());

        $repository->resetModules();

        $this->assertSame(['NewBlogProvider'], $manifest->getModulesData()->pluck('providers')->flatten()->all());
    }

    public function test_it_reads_its_own_paths_when_the_repository_scans_other_paths()
    {
        $repository = $this->bindRepository(__DIR__.'/stubs/valid');
        $location = $this->makeLocation([
            'Low' => ['name' => 'Low', 'priority' => 1, 'providers' => ['LowProvider'], 'files' => []],
            'High' => ['name' => 'High', 'priority' => 0, 'providers' => ['HighProvider'], 'files' => []],
            'Off' => ['name' => 'Off', 'providers' => ['OffProvider']],
        ]);
        $this->activator->setActiveByName('Low', true);
        $this->activator->setActiveByName('High', true);
        $this->activator->setActiveByName('Off', false);
        $manifest = $this->makeManifest(new Filesystem, [$location.'/*']);

        $this->assertNotSame($repository->getScanPaths(), $manifest->paths->all());
        $this->assertSame(['HighProvider', 'LowProvider'], $manifest->getProviders());
        $this->assertSame([
            ['module_directory' => $location.'/High', 'name' => 'High', 'priority' => 0, 'providers' => ['HighProvider'], 'files' => []],
            ['module_directory' => $location.'/Low', 'name' => 'Low', 'priority' => 1, 'providers' => ['LowProvider'], 'files' => []],
        ], $manifest->getModulesData()->values()->all());
    }

    private function bindRepository(string $location): LaravelFileRepository
    {
        $repository = (new LaravelFileRepository($this->app))->addLocation($location);
        $this->app->instance(RepositoryInterface::class, $repository);

        return $repository;
    }

    private function makeManifest(Filesystem $files, array $paths): ModuleManifest
    {
        return new ModuleManifest($files, $paths, $this->app->bootstrapPath('cache/modules.php'), $this->activator);
    }

    /**
     * @param  array<string, array<string, mixed>>  $modules  module.json contents keyed by module folder
     */
    private function makeLocation(array $modules): string
    {
        $location = sys_get_temp_dir().'/laravel-modules-'.uniqid();
        $this->beforeApplicationDestroyed(fn () => $this->app['files']->deleteDirectory($location));

        foreach ($modules as $folder => $json) {
            $this->app['files']->ensureDirectoryExists($location.'/'.$folder);
            $this->app['files']->put($location.'/'.$folder.'/module.json', json_encode($json));
        }

        return $location;
    }
}
