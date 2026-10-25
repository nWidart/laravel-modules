<?php

namespace Nwidart\Modules\Tests\Publishing;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\Module;
use Nwidart\Modules\Publishing\AssetPublisher;
use Nwidart\Modules\Tests\BaseTestCase;
use RuntimeException;

class PublisherTest extends BaseTestCase
{
    public function test_publish_without_a_console_fails_with_a_clear_message()
    {
        $module = new Module($this->app, 'Recipe', dirname(__DIR__).'/stubs/valid/Recipe');

        $publisher = (new AssetPublisher($module))
            ->setRepository($this->app[RepositoryInterface::class]);

        $this->assertNull($publisher->getConsole());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The 'console' property must instance of \\Illuminate\\Console\\Command.");

        $publisher->publish();
    }
}
