<?php

namespace Nwidart\Modules\Tests\Traits;

use Nwidart\Modules\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PathNamespaceTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->class = new UsePathNamespaceTrait;
    }

    public function test_studly_path()
    {
        $this->assertSame('Blog/Services', $this->class->studly_path('/blog/services'));
    }

    public function test_studly_namespace()
    {
        $this->assertSame('/blog/services', $this->class->studly_namespace('/blog/services'));
    }

    public function test_path_namespace()
    {
        $this->assertSame('Blog\Services', $this->class->path_namespace('/blog/services'));
    }

    public function test_module_namespace()
    {
        $this->assertSame('Modules\Blog/services', $this->class->module_namespace('blog/services'));
    }

    public function test_module_namespace_without_a_path()
    {
        $this->app['config']->set('modules.namespace', 'Modules');

        set_error_handler(function (int $errno, string $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        }, E_DEPRECATED);

        try {
            $this->assertSame('Modules\\Blog', $this->class->module_namespace('Blog'));
            $this->assertSame('Modules\\Blog', $this->class->module_namespace('Blog', null));
            $this->assertSame('Modules\\Blog', $this->class->module_namespace('Blog', ''));
            $this->assertSame('Modules\\Blog\\Models', $this->class->module_namespace('Blog', 'models'));
        } finally {
            restore_error_handler();
        }
    }

    public function test_clean_path()
    {
        $this->assertSame('blog/services', $this->class->clean_path('blog/services'));
        $this->assertSame('', $this->class->clean_path(''));
    }

    public function test_clean_path_removes_empty_segments_only()
    {
        $this->assertSame('blog/services', $this->class->clean_path('/blog//services/'));
        $this->assertSame('v1/0/blog', $this->class->clean_path('v1/0/blog'));
        $this->assertSame('0', $this->class->clean_path('0'));
        $this->assertSame('Blog\\0\\Services', $this->class->clean_path('Blog\\0\\Services', '\\'));
    }

    public function test_path_namespace_keeps_zero_segments()
    {
        $this->assertSame('Blog\\0\\Services', $this->class->path_namespace('blog/0/services'));
    }

    public function test_app_path()
    {
        $configPath = config('modules.paths.app_folder');
        $configPath = rtrim($configPath, '/');

        $this->assertSame($configPath, $this->class->app_path());
        $this->assertSame($configPath, $this->class->app_path(null));
        $this->assertSame('app/blog/services', $this->class->app_path('blog/services'));
    }

    #[DataProvider('appPathProvider')]
    public function test_app_path_with_app_folder(?string $appFolder, ?string $path, string $expected)
    {
        $this->app['config']->set('modules.paths.app_folder', $appFolder);

        $this->assertSame($expected, $this->class->app_path($path));
    }

    public static function appPathProvider(): array
    {
        return [
            'default, no path' => ['app/', null, 'app'],
            'default, relative path' => ['app/', 'blog/services', 'app/blog/services'],
            'default, path starts with app folder' => ['app/', 'app/Models', 'app/Models'],
            'default, repeated app folder' => ['app/', 'app/app/Models', 'app/Models'],
            'default, path is the app folder' => ['app/', 'app/', 'app'],
            'default, path is the app folder without slash' => ['app/', 'app', 'app'],
            'default, other segment with same characters' => ['app/', 'application/Foo', 'app/application/Foo'],
            'empty app folder falls back to app' => ['', 'Models', 'app/Models'],
            'null app folder falls back to app' => [null, 'Models', 'app/Models'],
            'custom app folder' => ['src/', 'Models', 'src/Models'],
            'custom app folder, path starts with it' => ['src/', 'src/Models', 'src/Models'],
            'custom app folder, path starts with app' => ['src/', 'app/Models', 'src/Models'],
            'custom app folder, path has both' => ['src/', 'src/app/Models', 'src/Models'],
            'leading slash in app folder, no path' => ['/src/', null, 'src'],
            'leading slash in app folder, path starts with it' => ['/src/', 'src/Models', 'src/Models'],
            'leading slash in app folder, path starts with app' => ['/src/', 'app/Models', 'src/Models'],
            'nested app folder' => ['src/app/', 'src/app/Models', 'src/app/Models'],
        ];
    }

    #[DataProvider('stripAppFolderProvider')]
    public function test_strip_app_folder(?string $appFolder, ?string $path, string $expected)
    {
        $this->app['config']->set('modules.paths.app_folder', $appFolder);

        $this->assertSame($expected, $this->class->strip_app_folder($path));
    }

    public static function stripAppFolderProvider(): array
    {
        return [
            'prefix with trailing slash' => ['app/', 'app/Http/Controllers', 'Http/Controllers'],
            'prefix without trailing slash' => ['app', 'app/Http/Controllers', 'Http/Controllers'],
            'lowercase segment after prefix' => ['app/', 'app/actions', 'actions'],
            'path equal to app folder' => ['app/', 'app', ''],
            'path without the prefix' => ['app/', 'Http/Controllers', 'Http/Controllers'],
            'same leading characters, other segment' => ['app/', 'application/Foo', 'application/Foo'],
            'app folder only matches mid-path' => ['app/', 'src/app/Foo', 'src/app/Foo'],
            'custom app folder keeps other paths' => ['domain/', 'database/factories', 'database/factories'],
            'custom app folder is stripped' => ['domain/', 'domain/Models', 'Models'],
            'nested app folder' => ['src/app/', 'src/app/Models', 'Models'],
            'leading slash in app folder' => ['/src/', 'src/Models', 'Models'],
            'null app folder' => [null, 'app/Models', 'app/Models'],
            'empty app folder' => ['', 'app/Models', 'app/Models'],
            'null path' => ['app/', null, ''],
        ];
    }
}
