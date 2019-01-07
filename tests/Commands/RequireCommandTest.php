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
        $this->json->shouldHaveReceived('set', [
            'require',
            [
                'test/test' => '^1.0.0',
            ],
        ]);
        $this->json->shouldHaveReceived('set', [
            'scripts',
            [
                'additional-scripts' => 'test2',
            ],
        ]);
    }

    public function testRequireDev()
    {
        $command = $this->mockCommand(true);

        $command->handle();

        $command->shouldHaveReceived('info', ['updated module composer']);
        $this->json->shouldHaveReceived('set', [
            'require-dev',
            [
                'test/test' => '^1.0.0',
            ],
        ]);
        $this->json->shouldHaveReceived('set', [
            'scripts',
            [
                'additional-scripts' => 'test2',
            ],
        ]);
    }

    protected function mockJson()
    {
        $json = \Mockery::mock('alias:' . Json::class);
        $json->shouldReceive('set');
        $json->shouldReceive('save');
        $json->shouldReceive('get')->with('require')->andReturn(null);
        $json->shouldReceive('get')->with('require-dev')->andReturn(null);
        $json->shouldReceive('get')->with('scripts')->andReturn(null);
        return $json;
    }

    protected function mockModule($json)
    {
        $module = \Mockery::mock(Module::class);
        $module->shouldReceive('json')->andReturn($json);
        return $module;
    }

    protected function mockInstaller($requireDev)
    {
        $installer = \Mockery::mock('overload:' . Installer::class);
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
        $command = \Mockery::mock(RequireCommand::class . '[info, getRootComposer, argument, option, getModule]')
            ->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('info');
        $command->shouldReceive('argument')->with('module')->andReturn('testModule');
        $command->shouldReceive('argument')->with('packageName')->andReturn('test/test');
        $command->shouldReceive('option')->with('dev')->andReturn($requireDev);
        $count = 0;
        $command->shouldReceive('getRootComposer')->andReturnUsing(function () use (&$count) {
            $count++;
            switch ($count) {
                case 1:
                    return [
                        'require' => [],
                        'scripts' => [
                            'some-scripts' => 'test',
                        ],
                    ];
                case 2:
                    return [
                        'require' => [
                            'test/test' => '^1.0.0',
                        ],
                        'require-dev' => [
                            'test/test' => '^1.0.0',
                        ],
                        'scripts' => [
                            'some-scripts' => 'test',
                            'additional-scripts' => 'test2',
                        ],
                    ];
                default:
                    throw new \Exception('expect call only twice.');
            }
        });
        $this->json = $this->mockJson();
        $module = $this->mockModule($this->json);
        $command->shouldReceive('getModule')->andReturn($module);
        $this->mockInstaller($requireDev);
        return $command;
    }
}
