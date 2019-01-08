<?php
/**
 * Created by zed.
 */

namespace Nwidart\Modules\Tests\Commands;

use Mockery\MockInterface;
use Nwidart\Modules\Commands\RequireCommand;
use Nwidart\Modules\Json;
use Nwidart\Modules\Module;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Tests\BaseTestCase;
use Symfony\Component\Process\Process;

/**
 * Class RequireCommandTest
 * @package Nwidart\Modules\Tests\Commands
 */
class RequireCommandTest extends BaseTestCase
{
    /**
     * @var MockInterface|Json
     */
    protected $json = null;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->runTestInSeparateProcess = true;
        $this->setPreserveGlobalState(false);
    }

    public function testRequire()
    {
        $command = $this->mockCommand(false);

        $command->handle();

        $command->shouldHaveReceived('info', ['updated module composer']);
        $command->shouldHaveReceived('putComposer', [
            '/testPath/composer.json',
            [
                'require' => [
                    'test/test' => '^1.0.0',
                ],
                'scripts' => [
                    'additional-scripts' => ['test2'],
                ],
            ],
        ]);
    }

    public function testRequireDev()
    {
        $command = $this->mockCommand(true);

        $command->handle();

        $command->shouldHaveReceived('info', ['updated module composer']);
        $command->shouldHaveReceived('putComposer', [
            '/testPath/composer.json',
            [
                'require-dev' => [
                    'test/test' => '^1.0.0',
                ],
                'scripts' => [
                    'additional-scripts' => ['test2'],
                ],
            ],
        ]);
    }

    protected function mockJson()
    {
        $json = \Mockery::mock('alias:' . Json::class);
        $json->shouldReceive('get')->with('require')->andReturn(null);
        $json->shouldReceive('get')->with('require-dev')->andReturn(null);
        $json->shouldReceive('get')->with('scripts')->andReturn(null);
        return $json;
    }

    protected function mockModule()
    {
        $module = \Mockery::mock(Module::class);
        $module->shouldReceive('getPath')->andReturn('/testPath');
        return $module;
    }

    protected function mockInstaller($requireDev)
    {
        $installer = \Mockery::mock('overload:' . Installer::class);
        $installer->shouldReceive('setConsole');
        $process = \Mockery::mock(Process::class);
        $process->shouldReceive('isSuccessful')->andReturn(true);
        $installer->shouldReceive('isRequireDev')->andReturn($requireDev);
        if ($requireDev) {
            $installer->shouldReceive('setRequireDev');
        }
        $installer->shouldReceive('run')->andReturn($process);
    }

    protected function mockCommand($requireDev)
    {
        $command = \Mockery::mock(RequireCommand::class . '[info, getComposer, argument, option, getModule, putComposer]')
            ->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('info');
        $command->shouldReceive('argument')->with('module')->andReturn('testModule');
        $command->shouldReceive('argument')->with('packageName')->andReturn('test/test');
        $command->shouldReceive('option')->with('dev')->andReturn($requireDev);
        $command->shouldReceive('putComposer');
        $count = 0;
        $command->shouldReceive('getComposer')->andReturnUsing(function () use (&$count) {
            $count++;
            switch ($count) {
                case 1: // first call for root composer.json
                    return [
                        'require' => [],
                        'scripts' => [
                            'some-scripts' => 'test',
                        ],
                    ];
                case 2: // second call for root composer.json
                    return [
                        'require' => [
                            'test/test' => '^1.0.0',
                        ],
                        'require-dev' => [
                            'test/test' => '^1.0.0',
                        ],
                        'scripts' => [
                            'some-scripts' => 'test',
                            'additional-scripts' => ['test2'],
                        ],
                    ];
                case 3: // third call for module composer.json
                    return [];
                default:
                    throw new \Exception('expect call only 3 times.');
            }
        });
        $this->json = $this->mockJson();
        $module = $this->mockModule();
        $command->shouldReceive('getModule')->andReturn($module);
        $this->mockInstaller($requireDev);
        return $command;
    }
}
