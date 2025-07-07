<?php

namespace Nwidart\Modules\Tests\Traits;

use Nwidart\Modules\Helpers\Path;
use Nwidart\Modules\Tests\BaseTestCase;

class PathNamespaceTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_converts_to_studly_path()
    {
        $this->assertSame('Modules/User/App/Models/User', Path::studly('Modules/User/app/Models/User'));
    }

    public function test_converts_namespace_with_studly_path()
    {
        $this->assertSame('Modules\\User\\App\\Models\\User', Path::studly('Modules\\User\\app\\Models\\User', '\\'));
    }

    public function test_converts_to_studly_namespace()
    {
        $this->assertSame('Modules\User\App\Models\User', Path::studly('Modules/User/app/Models/User', '\\'));
    }

    public function test_converts_custom_namespace_with_studly_namespace()
    {
        $this->assertSame('Modules\\\\User\\\\App\\\\Models\\\\User', Path::studly('Modules\\\\User\\\\app\\\\Models\\\\User', '\\\\'));
    }

    public function test_generates_path_namespace()
    {
        $this->assertSame('User\App\Models\User', $this->path_namespace('User/app/Models/User'));
    }

    public function test_generates_module_namespace()
    {
        $this->assertSame('Modules\User', $this->module_namespace('user'));
        $this->assertSame('Modules\User\App\Models\User', $this->module_namespace('user', 'app/Models/User'));
    }

    public function test_cleans_path()
    {
        $this->assertSame('blog/services', Path::clean('blog//services'));
        $this->assertSame('', Path::clean('//'));
        $this->assertSame('', Path::clean(''));
    }

    public function test_cleans_namespace_with_clean_path()
    {
        $this->assertSame('Modules\User\App\Models\User', Path::clean('Modules\\\\User/App\\Models\User\\//', '\\'));
        $this->assertSame('', Path::clean('\\'));
    }

    public function test_identifies_app_path()
    {
        $this->assertTrue($this->is_app_path('app/Models/User'));
        $this->assertFalse($this->is_app_path('src/Models/User'));
    }

    public function test_recognizes_custom_app_path()
    {
        config(['modules.paths.app' => 'src/']);
        $this->assertTrue($this->is_app_path('app/Models/User'));
        $this->assertTrue($this->is_app_path('src/Models/User'));
    }

    public function test_generates_app_path()
    {
        $app_path = $this->app_path();

        $this->assertSame('app/Models/User', $this->app_path('Models/User'));
        $this->assertSame($app_path, $this->app_path(null));
    }

    public function test_generates_custom_app_path()
    {
        config(['modules.paths.app' => 'src/']);

        $this->assertSame('src/Models/User', $this->app_path('Models/User'));
    }

    public function test_generates_root_app_path()
    {
        config(['modules.paths.app' => '/']);

        $this->assertSame('', $this->app_path());
    }

    public function test_removes_duplicate_app_path()
    {
        $this->assertSame('app/Models/User', $this->app_path('app/Models/User'));
        $this->assertSame('app/Models/User', $this->app_path('app/app/Models/User'));

        config(['modules.paths.app' => 'src/']);
        $this->assertSame('src/Models/User', $this->app_path('src/Models/User'));
        $this->assertSame('src/Models/User', $this->app_path('src/src/Models/User'));

        config(['modules.paths.app' => '/']);
        $this->assertSame('Models/User', $this->app_path('app/Models/User'));
        $this->assertSame('Models/User', $this->app_path('app/app/Models/User'));
    }

    public function test_removes_duplicate_app_path_regardless_of_case()
    {
        $this->assertSame('app/Models/User', $this->app_path('app/Models/User'));
        $this->assertSame('app/Models/User', $this->app_path('App/App/Models/User'));
        $this->assertSame('app/Models/User', $this->app_path('APP/APP/Models/User'));
    }

    public function test_generates_app_path_namespace()
    {
        $this->assertSame('App', $this->app_path_namespace());
        $this->assertSame('App', $this->app_path_namespace(null));
        $this->assertSame('App\Models\User', $this->app_path_namespace('app/Models/User'));
        $this->assertSame('App\Models\User', $this->app_path_namespace('Models/User'));
    }

    public function test_generates_modules_app_path_namespace()
    {
        $this->assertSame('Modules\User\App', $this->modules_app_path_namespace('user'));
        $this->assertSame('Modules\User\App', $this->modules_app_path_namespace('user', null));
        $this->assertSame('Modules\User\App\Models\User', $this->modules_app_path_namespace('user', 'app/Models/User'));
        $this->assertSame('Modules\User\App\Models\User', $this->modules_app_path_namespace('user', 'Models/User'));
    }
}
