<?php

namespace Nwidart\Modules\Tests;

use Error;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\FileRepository;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Tests\Fixtures\BrokenModules\BlogServiceProvider;
use Nwidart\Modules\Tests\Fixtures\BrokenModules\CartServiceProvider;

class SkipBrokenModulesTest extends BaseTestCase
{
    private const MISSING_PROVIDER = 'Modules\Cart\Providers\MissingServiceProvider';

    /**
     * @var list<string>
     */
    private array $directories = [];

    protected function tearDown(): void
    {
        foreach ($this->directories as $directory) {
            $this->app['files']->deleteDirectory($directory);
        }

        $this->app['files']->delete($this->app->bootstrapPath('cache/modules.php'));
        $this->app[ActivatorInterface::class]->reset();
        $this->app[RepositoryInterface::class]->resetModules();

        parent::tearDown();
    }

    public function test_an_invalid_module_json_stops_the_boot_by_default()
    {
        $shop = $this->writeModule('Shop', '{ broken');

        $this->expectException(InvalidJsonException::class);
        // The exception keeps the absolute path; only the recorded reason is made relative.
        $this->expectExceptionMessage('Error processing file: '.$shop.'/module.json. Error: Syntax error');

        $this->registerModules();
    }

    public function test_a_missing_provider_class_stops_the_boot_by_default()
    {
        $this->writeModule('Cart', ['name' => 'Cart', 'providers' => [self::MISSING_PROVIDER]]);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Class "'.self::MISSING_PROVIDER.'" not found');

        $this->registerModules();
    }

    public function test_it_skips_a_module_with_an_invalid_module_json()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeModule('Blog', ['name' => 'Blog', 'providers' => [BlogServiceProvider::class]]);
        $this->writeModule('Shop', '{ broken');

        $this->registerModules();

        $this->assertTrue($this->app->bound('broken-modules.blog'));
        $this->assertSame(['blog'], array_keys($this->repository()->all()));
        $this->assertSame([
            'Shop' => 'Error processing file: modules/Shop/module.json. Error: Syntax error',
        ], $this->repository()->getBrokenModules());
    }

    public function test_the_reason_for_an_invalid_module_json_holds_no_absolute_path()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeModule('Shop', '{ broken');

        $this->registerModules();

        $reason = $this->repository()->getBrokenModules()['Shop'];
        $this->assertStringNotContainsString(base_path(), $reason);
        $this->assertStringContainsString(' modules/Shop/module.json.', $reason);
    }

    public function test_it_skips_every_provider_alias_and_file_of_a_module_with_a_missing_provider_class()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeModule('Blog', ['name' => 'Blog', 'providers' => [BlogServiceProvider::class]]);
        $cart = $this->writeModule('Cart', [
            'name' => 'Cart',
            'providers' => [CartServiceProvider::class, self::MISSING_PROVIDER],
            'aliases' => ['SkipBrokenModulesCartAlias' => Str::class],
            'files' => ['helpers.php'],
        ]);
        $this->app['files']->put($cart.'/helpers.php', '<?php throw new RuntimeException("Cart files must not be loaded.");');

        $this->registerModules();

        $this->assertTrue($this->app->bound('broken-modules.blog'));
        $this->assertFalse($this->app->bound('broken-modules.cart'));
        $this->assertArrayNotHasKey('SkipBrokenModulesCartAlias', AliasLoader::getInstance()->getAliases());
        $this->assertSame([BlogServiceProvider::class], $this->app[ModuleManifest::class]->getProviders());
        $this->assertSame([
            'Cart' => 'Provider class ['.self::MISSING_PROVIDER.'] not found.',
        ], $this->repository()->getBrokenModules());
    }

    public function test_a_disabled_module_with_a_missing_provider_class_is_not_reported()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeModule('Cart', ['name' => 'Cart', 'providers' => [self::MISSING_PROVIDER]], enabled: false);

        $this->registerModules();

        $this->assertSame([], $this->repository()->getBrokenModules());
    }

    public function test_it_logs_each_skipped_module_once()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeModule('Shop', '{ broken');
        $this->writeModule('Cart', ['name' => 'Cart', 'providers' => [self::MISSING_PROVIDER]]);
        Log::spy();

        $this->registerModules();
        $this->repository()->resetModules()->scan();

        $shopReason = 'Error processing file: modules/Shop/module.json. Error: Syntax error';
        $cartReason = 'Provider class ['.self::MISSING_PROVIDER.'] not found.';
        Log::shouldHaveReceived('error')->twice();
        Log::shouldHaveReceived('error', [
            "Module [Shop] was skipped because it is broken: {$shopReason}",
            ['module' => 'Shop', 'reason' => $shopReason],
        ])->once();
        Log::shouldHaveReceived('error', [
            "Module [Cart] was skipped because it is broken: {$cartReason}",
            ['module' => 'Cart', 'reason' => $cartReason],
        ])->once();
    }

    public function test_a_logger_that_fails_does_not_stop_the_boot()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeModule('Shop', '{ broken');
        Log::shouldReceive('error')->andThrow(new \RuntimeException('The log file cannot be written.'));

        $this->registerModules();

        $this->assertArrayHasKey('Shop', $this->repository()->getBrokenModules());
    }

    public function test_a_manifest_reading_its_own_paths_skips_an_invalid_module_json()
    {
        config(['modules.skip_broken_modules' => true]);
        $location = $this->makeLocation();
        $this->writeModule('Blog', ['name' => 'Blog', 'providers' => [BlogServiceProvider::class]], location: $location);
        $this->writeModule('Shop', '{ broken', location: $location);

        $manifest = $this->makeManifest([$location.'/*']);

        $this->assertSame([BlogServiceProvider::class], $manifest->getProviders());
        // The location is outside the application base path: only the module folder is shown.
        $this->assertSame([
            'Shop' => 'Error processing file: Shop/module.json. Error: Syntax error',
        ], $this->repository()->getBrokenModules());
    }

    public function test_a_manifest_reading_its_own_paths_skips_a_module_with_a_missing_provider_class()
    {
        config(['modules.skip_broken_modules' => true]);
        $location = $this->makeLocation();
        $this->writeModule('Blog', ['name' => 'Blog', 'providers' => [BlogServiceProvider::class]], location: $location);
        $this->writeModule('Cart', ['name' => 'Cart', 'providers' => [self::MISSING_PROVIDER]], location: $location);

        $manifest = $this->makeManifest([$location.'/*']);

        $this->assertSame([BlogServiceProvider::class], $manifest->getProviders());
        $this->assertSame(['Cart'], array_keys($this->repository()->getBrokenModules()));
    }

    public function test_a_manifest_reading_its_own_paths_fails_on_an_invalid_module_json_by_default()
    {
        $location = $this->makeLocation();
        $this->writeModule('Shop', '{ broken', location: $location);

        $this->expectException(Error::class);

        $this->makeManifest([$location.'/*'])->getProviders();
    }

    /**
     * Write a module.json, and set the module status when the module has a name.
     *
     * @param  array<string, mixed>|string  $json  The module.json attributes, or the raw file contents.
     * @return string The module directory.
     */
    private function writeModule(string $folder, array|string $json, bool $enabled = true, ?string $location = null): string
    {
        $directory = ($location ?? base_path('modules')).'/'.$folder;
        $this->directories[] = $directory;

        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put($directory.'/module.json', is_string($json) ? $json : json_encode($json));

        if (is_array($json)) {
            $this->app[ActivatorInterface::class]->setActiveByName($json['name'], $enabled);
        }

        return $directory;
    }

    private function makeLocation(): string
    {
        $location = sys_get_temp_dir().'/laravel-modules-'.uniqid();
        $this->directories[] = $location;

        return $location;
    }

    private function makeManifest(array $paths): ModuleManifest
    {
        return new ModuleManifest(new Filesystem, $paths, $this->app->bootstrapPath('cache/modules.php'), $this->app[ActivatorInterface::class]);
    }

    private function repository(): FileRepository
    {
        return $this->app[RepositoryInterface::class];
    }

    /**
     * Run the provider's module registration again, as a boot does, so it reads the modules written by the test.
     */
    private function registerModules(): void
    {
        $this->repository()->resetModules();
        $this->app->forgetInstance(ModuleManifest::class);

        $provider = $this->app->getProvider(LaravelModulesServiceProvider::class);

        (fn () => $this->registerModules())->call($provider);
    }
}
