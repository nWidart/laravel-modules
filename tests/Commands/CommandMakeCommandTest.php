<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Contracts\RepositoryInterface;

uses(\Spatie\Snapshots\MatchesSnapshots::class);

beforeEach(function () {
    $this->modulePath = base_path('modules/Blog');
    $this->finder = $this->app['files'];
    $this->artisan('module:make', ['name' => ['Blog']]);
});

afterEach(function () {
    $this->app[RepositoryInterface::class]->delete('Blog');
});

it('generates a new console command class', function () {
    $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

    expect(is_file($this->modulePath . '/Console/MyAwesomeCommand.php'))->toBeTrue();
    expect($code)->toBe(0);
});

it('generated correct file with content', function () {
    $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Console/MyAwesomeCommand.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('uses set command name in class', function () {
    $code = $this->artisan(
        'module:make-command',
        ['name' => 'MyAwesomeCommand', 'module' => 'Blog', '--command' => 'my:awesome']
    );

    $file = $this->finder->get($this->modulePath . '/Console/MyAwesomeCommand.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace', function () {
    $this->app['config']->set('modules.paths.generator.command.path', 'Commands');

    $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Commands/AwesomeCommand.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});

it('can change the default namespace specific', function () {
    $this->app['config']->set('modules.paths.generator.command.namespace', 'Commands');

    $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

    $file = $this->finder->get($this->modulePath . '/Console/AwesomeCommand.php');

    $this->assertMatchesSnapshot($file);
    expect($code)->toBe(0);
});
