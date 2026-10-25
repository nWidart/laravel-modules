<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ListCommandTest extends BaseTestCase
{
    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_can_list_modules()
    {
        $code = $this->artisan('module:list');

        // We just want to make sure nothing throws an exception inside the list command
        $this->assertTrue(true);
        $this->assertSame(0, $code);
    }

    public function test_it_lists_priority_zero_when_module_json_has_no_priority()
    {
        $path = $this->app[RepositoryInterface::class]->find('Blog')->getPath().'/module.json';
        $json = json_decode($this->app['files']->get($path), true);
        unset($json['priority']);
        $this->app['files']->put($path, json_encode($json));

        Artisan::call('module:list');

        $this->assertMatchesRegularExpression('/Blog .*modules\/Blog \[0\]/', Artisan::output());
    }
}
