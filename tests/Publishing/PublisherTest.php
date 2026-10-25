<?php

namespace Nwidart\Modules\Tests\Publishing;

use Illuminate\Console\Command;
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

    public function test_a_publisher_without_a_repository_fails_with_a_clear_message()
    {
        $module = new Module($this->app, 'Recipe', dirname(__DIR__).'/stubs/valid/Recipe');

        $publisher = (new AssetPublisher($module))->setConsole(new Command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The 'repository' property is not set: call setRepository() on the publisher of module [Recipe].");

        $publisher->publish();
    }

    public function test_get_repository_without_a_repository_fails_with_a_clear_message()
    {
        $module = new Module($this->app, 'Recipe', dirname(__DIR__).'/stubs/valid/Recipe');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The 'repository' property is not set");

        (new AssetPublisher($module))->getRepository();
    }

    public function test_the_success_message_defaults_to_an_empty_string()
    {
        $module = new Module($this->app, 'Recipe', dirname(__DIR__).'/stubs/valid/Recipe');

        $publisher = new class($module) extends AssetPublisher
        {
            public function successMessage(): string
            {
                return $this->success;
            }
        };

        $this->assertSame('', $publisher->successMessage());
    }
}
