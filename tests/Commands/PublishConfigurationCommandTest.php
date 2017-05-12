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

        $this->mockLaravelPublish([$arguments]);

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

        $this->mockLaravelPublish([$arguments]);

        $this->artisan('module:publish-config', ['module' => $moduleName]);
    }

    /** @test */
    public function multiple_modules_having_any_count_service_provider_will_publish_them()
    {
        $serviceProvider1 = 'Vendor1/PackageNamespace/ServiceProvider';
        $moduleName1 = 'Name1Module';
        $module1 = $this->getFakeModule($moduleName1, [
            $serviceProvider1,
        ]);

        $serviceProvider2 = 'Vendor2/PackageNamespace/ServiceProvider';
        $moduleName2 = 'Name2Module';
        $module2 = $this->getFakeModule($moduleName2, [
            $serviceProvider2,
        ]);

        $mock = $this->createMock(Repository::class);
        $mock->expects($this->once())
            ->method('enabled')
            ->willReturn([$module1, $module2]);
        $mock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([$moduleName1], [$moduleName2])
            ->willReturnOnConsecutiveCalls($module1, $module2);

        $this->app->instance('modules', $mock);

        $argumentsBase = [
            '--force' => false,
            '--tag' => [
                'config',
            ],
            'command' => 'vendor:publish',
        ];
        $arguments1 = $argumentsBase + [
                '--provider' => $serviceProvider1,
            ];
        $arguments2 = $argumentsBase + [
                '--provider' => $serviceProvider1,
            ];

        $this->mockLaravelPublish([$arguments1, $arguments2]);

        $this->artisan('module:publish-config');
    }

    /**
     * @param array $arguments
     * @throws \InvalidArgumentException
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

        $values = [];
        foreach ($arguments as $argument) {
            $values[] = [
                new ArrayInput($argument),
                $this->isInstanceOf(OutputInterface::class),
            ];
        }
        $command->expects($this->exactly(count($arguments)))
            ->method('run')
            ->withConsecutive(
                ...$values
            );
        $this->app->extend('command.vendor.publish', function() use ($command) {
            return $command;
        });
    }

    /**
     * @param string $name
     * @param array $providers
     * @return \PHPUnit_Framework_MockObject_MockObject
     * @throws \InvalidArgumentException
     * @throws \PHPUnit_Framework_Exception
     * @throws \PHPUnit_Framework_MockObject_RuntimeException
     */
    private function getFakeModule($name, array $providers)
    {
        $mockBuilder = $this->getMockBuilder('TestModule')
            ->setMethods(['getName']);
        $PHPUnitFrameworkMockObjectMockBuilder = $mockBuilder
            ->getMock();
        $module1 = $PHPUnitFrameworkMockObjectMockBuilder;
        $module1->method('getName')
            ->willReturn($name);
        $module1->providers = $providers;

        return $module1;
    }
}
