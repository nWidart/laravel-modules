<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ComposerUpdateCommandTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_moves_the_legacy_app_namespace_to_the_module_namespace()
    {
        $this->setAutoload(['Modules\\Blog\\App\\' => 'app/', 'Modules\\Blog\\Database\\Seeders\\' => 'database/seeders/']);

        $code = $this->artisan('module:composer-update', ['module' => ['Blog']]);

        $this->assertSame(
            ['Modules\\Blog\\' => 'app/', 'Modules\\Blog\\Database\\Seeders\\' => 'database/seeders/'],
            $this->getAutoload()
        );
        $this->assertSame(0, $code);
    }

    public function test_it_uses_the_configured_module_namespace()
    {
        $this->app['config']->set('modules.namespace', 'Domains');
        $this->setAutoload(['Domains\\Blog\\App\\' => 'app/']);

        $this->artisan('module:composer-update', ['module' => ['Blog']]);

        $this->assertSame(['Domains\\Blog\\' => 'app/'], $this->getAutoload());
    }

    public function test_it_keeps_the_folder_of_the_legacy_entry()
    {
        $this->app['config']->set('modules.paths.app_folder', 'src/');
        $this->setAutoload(['Modules\\Blog\\Src\\' => 'src/']);

        $this->artisan('module:composer-update', ['module' => ['Blog']]);

        $this->assertSame(['Modules\\Blog\\' => 'src/'], $this->getAutoload());
    }

    public function test_it_leaves_an_up_to_date_autoload_unchanged()
    {
        $this->setAutoload(['Modules\\Blog\\' => 'app/']);

        $this->artisan('module:composer-update', ['module' => ['Blog']]);

        $this->assertSame(['Modules\\Blog\\' => 'app/'], $this->getAutoload());
    }

    public function test_it_keeps_the_formatting_of_composer_json()
    {
        file_put_contents($this->composerPath(), <<<'JSON'
{
    "name": "nwidart/blog",
    "description": "Café module",
    "extra": {
        "laravel": {
            "providers": [],
            "aliases": {}
        }
    },
    "autoload": {
        "psr-4": {
            "Modules\\Blog\\App\\": "app/",
            "Modules\\Blog\\Database\\Factories\\": "database/factories/"
        }
    }
}

JSON);

        $this->artisan('module:composer-update', ['module' => ['Blog']]);

        $this->assertSame(<<<'JSON'
{
    "name": "nwidart/blog",
    "description": "Café module",
    "extra": {
        "laravel": {
            "providers": [],
            "aliases": {}
        }
    },
    "autoload": {
        "psr-4": {
            "Modules\\Blog\\": "app/",
            "Modules\\Blog\\Database\\Factories\\": "database/factories/"
        }
    }
}

JSON, file_get_contents($this->composerPath()));
    }

    private function composerPath(): string
    {
        return $this->getModuleBasePath().'/composer.json';
    }

    private function setAutoload(array $psr4): void
    {
        $composer = json_decode(file_get_contents($this->composerPath()), true);
        $composer['autoload']['psr-4'] = $psr4;

        file_put_contents($this->composerPath(), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function getAutoload(): array
    {
        return json_decode(file_get_contents($this->composerPath()), true)['autoload']['psr-4'];
    }
}
