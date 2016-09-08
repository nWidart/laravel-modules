<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class MakeRequestCommandTest extends BaseTestCase
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
    public function it_generates_a_new_form_request_class()
    {
        $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Http/Requests/CreateBlogPostRequest.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-request', ['name' => 'CreateBlogPostRequest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Http/Requests/CreateBlogPostRequest.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBlogPostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}

TEXT;
    }
}
