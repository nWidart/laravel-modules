<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class ControllerCommandTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_controller_class()
    {
        $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Controllers/MyController.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-controller', ['controller' => 'MyController', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    /** @test */
    public function it_appends_controller_to_name_if_not_present()
    {
        $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Controllers/MyController.php'));
    }

    /** @test */
    public function it_appends_controller_to_class_name_if_not_present()
    {
        $this->artisan('module:make-controller', ['controller' => 'My', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    /** @test */
    public function it_generates_a_plain_controller()
    {
        $this->artisan('module:make-controller', [
            'controller' => 'MyController',
            'module' => 'Blog',
            '--plain' => true,
        ]);

        $file = $this->finder->get($this->modulePath . '/Http/Controllers/MyController.php');

        $this->assertEquals($this->expectedPlainContent(), $file);
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class MyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('blog::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('blog::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request \$request
     * @return Response
     */
    public function store(Request \$request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('blog::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('blog::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request \$request
     * @return Response
     */
    public function update(Request \$request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}

TEXT;
    }

    private function expectedPlainContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Routing\Controller;

class MyController extends Controller
{
}

TEXT;
    }
}
