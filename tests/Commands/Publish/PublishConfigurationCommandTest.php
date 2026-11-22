<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class PublishConfigurationCommandTest extends BaseTestCase
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

    public function test_it_succeeds_for_an_existing_module()
    {
        $code = $this->artisan('module:publish-config', ['module' => 'Blog']);

        $this->assertSame(0, $code);
    }

    public function test_it_prints_an_error_line_for_an_unknown_module()
    {
        $code = $this->artisan('module:publish-config', ['module' => 'Nope']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Module [Nope] does not exist.', $this->app[Kernel::class]->output());
    }
}
