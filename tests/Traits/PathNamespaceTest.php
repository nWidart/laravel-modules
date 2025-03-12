<?php

namespace Nwidart\Modules\Tests\Traits;

use Nwidart\Modules\Tests\BaseTestCase;

class PathNamespaceTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->path_namespace = new UsePathNamespaceTrait;
    }

    public function test_converts_to_studly_path()
    {
        $this->assertSame('Modules/User/App/Models/User', $this->path_namespace->studly_path('Modules/User/app/Models/User'));
    }

    public function test_converts_namespace_with_studly_path()
    {
        $this->assertSame('Modules\\User\\App\\Models\\User', $this->path_namespace->studly_path('Modules\\User\\app\\Models\\User', '\\'));
    }

    public function test_converts_to_studly_namespace()
    {
        $this->assertSame('Modules\User\App\Models\User', $this->path_namespace->studly_namespace('Modules/User/app/Models/User'));
    }

    public function test_converts_custom_namespace_with_studly_namespace()
    {
        $this->assertSame('Modules\\\\User\\\\App\\\\Models\\\\User', $this->path_namespace->studly_namespace('Modules\\\\User\\\\app\\\\Models\\\\User', '\\\\'));
    }

    public function test_generates_path_namespace()
    {
        $this->assertSame('User\App\Models\User', $this->path_namespace->path_namespace('User/app/Models/User'));
    }

    public function test_generates_module_namespace()
    {
        $this->assertSame('Modules\User', $this->path_namespace->module_namespace('user'));
        $this->assertSame('Modules\User\App\Models\User', $this->path_namespace->module_namespace('user', 'app/Models/User'));
    }

    public function test_cleans_path()
    {
        $this->assertSame('blog/services', $this->path_namespace->clean_path('blog//services'));
        $this->assertSame('', $this->path_namespace->clean_path('//'));
        $this->assertSame('', $this->path_namespace->clean_path(''));
    }

    public function test_cleans_namespace_with_clean_path()
    {
        $this->assertSame('Modules\User\App\Models\User', $this->path_namespace->clean_path('Modules\\\\User/App\\Models\User\\//', '\\'));
        $this->assertSame('', $this->path_namespace->clean_path('\\'));
    }

    public function test_identifies_app_path()
    {
        $this->assertTrue($this->path_namespace->is_app_path('app/Models/User'));
        $this->assertFalse($this->path_namespace->is_app_path('src/Models/User'));
    }

    public function test_recognizes_custom_app_path()
    {
        config(['modules.paths.app' => 'src/']);
        $this->assertTrue($this->path_namespace->is_app_path('app/Models/User'));
        $this->assertTrue($this->path_namespace->is_app_path('src/Models/User'));
    }

    public function test_generates_app_path()
    {
        $app_path = $this->path_namespace->app_path();

        $this->assertSame('app/Models/User', $this->path_namespace->app_path('Models/User'));
        $this->assertSame($app_path, $this->path_namespace->app_path(null));
    }

    public function test_generates_custom_app_path()
    {
        config(['modules.paths.app' => 'src/']);

        $this->assertSame('src/Models/User', $this->path_namespace->app_path('Models/User'));
    }

    public function test_generates_root_app_path()
    {
        config(['modules.paths.app' => '/']);

        $this->assertSame('Models/User', $this->path_namespace->app_path('Models/User'));
    }

    public function test_removes_duplicate_app_path()
    {
        $this->assertSame('app/Models/User', $this->path_namespace->app_path('app/Models/User'));
        $this->assertSame('app/Models/User', $this->path_namespace->app_path('app/app/Models/User'));

        config(['modules.paths.app' => 'src/']);
        $this->assertSame('src/Models/User', $this->path_namespace->app_path('src/Models/User'));
        $this->assertSame('src/Models/User', $this->path_namespace->app_path('src/src/Models/User'));

        config(['modules.paths.app' => '/']);
        $this->assertSame('Models/User', $this->path_namespace->app_path('app/Models/User'));
        $this->assertSame('Models/User', $this->path_namespace->app_path('app/app/Models/User'));
    }

    public function test_removes_duplicate_app_path_regardless_of_case()
    {
        $this->assertSame('app/Models/User', $this->path_namespace->app_path('app/Models/User'));
        $this->assertSame('app/Models/User', $this->path_namespace->app_path('App/App/Models/User'));
        $this->assertSame('app/Models/User', $this->path_namespace->app_path('APP/APP/Models/User'));
    }

    public function test_generates_app_path_namespace()
    {
        $this->assertSame('App', $this->path_namespace->app_path_namespace());
        $this->assertSame('App', $this->path_namespace->app_path_namespace(null));
        $this->assertSame('App\Models\User', $this->path_namespace->app_path_namespace('app/Models/User'));
        $this->assertSame('App\Models\User', $this->path_namespace->app_path_namespace('Models/User'));
    }

    public function test_generates_modules_app_path_namespace()
    {
        $this->assertSame('Modules\User\App', $this->path_namespace->modules_app_path_namespace('user'));
        $this->assertSame('Modules\User\App', $this->path_namespace->modules_app_path_namespace('user', null));
        $this->assertSame('Modules\User\App\Models\User', $this->path_namespace->modules_app_path_namespace('user', 'app/Models/User'));
        $this->assertSame('Modules\User\App\Models\User', $this->path_namespace->modules_app_path_namespace('user', 'Models/User'));
    }
}
