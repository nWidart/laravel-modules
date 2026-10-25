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
        $repository = $this->app[RepositoryInterface::class];

        foreach (['Blog', 'Shop'] as $module) {
            if ($repository->has($module)) {
                $repository->delete($module);
            }
        }

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

    public function test_it_lists_modules_by_priority_in_ascending_order_by_default()
    {
        $this->createModuleWithPriority('Shop', 1);
        $this->setModulePriority('Blog', 2);

        $code = $this->artisan('module:list', ['--only' => 'priority']);

        $this->assertSame(0, $code);
        $this->assertModulesListedInOrder(['Shop', 'Blog'], Artisan::output());
    }

    public function test_it_lists_modules_by_priority_in_descending_order()
    {
        $this->createModuleWithPriority('Shop', 1);
        $this->setModulePriority('Blog', 2);

        $code = $this->artisan('module:list', ['--only' => 'priority', '--direction' => 'desc']);

        $this->assertSame(0, $code);
        $this->assertModulesListedInOrder(['Blog', 'Shop'], Artisan::output());
    }

    private function createModuleWithPriority(string $name, int $priority): void
    {
        $this->createModule($name);
        $this->setModulePriority($name, $priority);
    }

    private function setModulePriority(string $name, int $priority): void
    {
        $this->app[RepositoryInterface::class]->find($name)->json()->set('priority', $priority)->save();
    }

    private function assertModulesListedInOrder(array $expected, string $output): void
    {
        preg_match_all('/\] (Blog|Shop) /', $output, $matches);

        $this->assertSame($expected, $matches[1]);
    }
}
