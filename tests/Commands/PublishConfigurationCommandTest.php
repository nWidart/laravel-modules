<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Foundation\Console\VendorPublishCommand;
use Nwidart\Modules\Repository;
use Nwidart\Modules\Tests\BaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PublishConfigurationCommandTest
 * @package Nwidart\Modules\Tests\Commands
 */
class PublishConfigurationCommandTest extends BaseTestCase
{
    /** @test */
    public function module_having_one_service_provider_will_publish_it()
    {
        $mock = $this->createMock(Repository::class);
        $module = new \stdClass();
        $serviceProvider = 'Vendor1/PackageNamespace/ServiceProvider';
        $module->providers = [
            $serviceProvider,
        ];

        $mock->expects($this->once())
            ->method('find')
            ->willReturn($module);

        $this->app->instance('modules', $mock);

        $arguments = [
            '--provider' => $serviceProvider,
            '--force' => false,
            '--tag' => [
                'config',
            ],
            'command' => 'vendor:publish',
        ];

        $this->mockLaravelPublish($arguments);

        $this->artisan('module:publish-config', ['module' => 'Blog']);
    }

    /** @test */
    public function module_having_two_service_providers_will_publish_default_service_provider_from_module_name()
    {
        $mock = $this->createMock(Repository::class);
        $module = new \stdClass();
        $module->providers = [
            'Vendor1/PackageNamespace/ServiceProvider',
            'Vendor2/PackageNamespace/ServiceProvider',
        ];

        $mock->expects($this->once())
            ->method('find')
            ->willReturn($module);

        $this->app->instance('modules', $mock);

        $moduleName = 'Blog';
        $arguments = [
            '--provider' => 'Modules\\' . $moduleName . '\Providers\\' . $moduleName . 'ServiceProvider',
            '--force' => false,
            '--tag' => [
                'config',
            ],
            'command' => 'vendor:publish',
        ];

        $this->mockLaravelPublish($arguments);

        $this->artisan('module:publish-config', ['module' => $moduleName]);
    }

    /**
     * @param array $arguments
     * @throws \PHPUnit_Framework_Exception
     * @throws \PHPUnit_Framework_MockObject_RuntimeException
     */
    private function mockLaravelPublish(array $arguments)
    {
        $command = $this->createMock(VendorPublishCommand::class);

        $command->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $command->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('vendor:publish'));
        $command->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue(!null));
        $command->expects($this->once())
            ->method('getAliases')
            ->will($this->returnValue([]));

        $command->expects($this->once())
            ->method('run')
            ->with(
                new ArrayInput($arguments),
                $this->isInstanceOf(OutputInterface::class)
            );

        $this->app->instance('command.vendor.publish', $command);
    }
}
