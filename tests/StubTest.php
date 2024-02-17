<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Illuminate\Support\Str;
use Nwidart\Modules\Support\Stub;

beforeEach(function () {
    $this->finder = $this->app['files'];
});

afterEach(function () {
    $this->finder->delete([
        base_path('my-command.php'),
        base_path('stub-override-exists.php'),
        base_path('stub-override-not-exists.php'),
    ]);
});

it('initialises a stub instance', function () {
    $stub = new Stub('/model.stub', [
        'NAME' => 'Name',
    ]);

    expect(Str::contains($stub->getPath(), 'src/Commands/stubs/model.stub'))->toBeTrue();
    expect($stub->getReplaces())->toEqual(['NAME' => 'Name', ]);
});

it('sets new replaces array', function () {
    $stub = new Stub('/model.stub', [
        'NAME' => 'Name',
    ]);

    $stub->replace(['VENDOR' => 'MyVendor', ]);
    expect($stub->getReplaces())->toEqual(['VENDOR' => 'MyVendor', ]);
});

it('stores stub to specific path', function () {
    $stub = new Stub('/command.stub', [
        'COMMAND_NAME' => 'my:command',
        'NAMESPACE' => 'Blog\Commands',
        'CLASS' => 'MyCommand',
    ]);

    $stub->saveTo(base_path(), 'my-command.php');

    expect($this->finder->exists(base_path('my-command.php')))->toBeTrue();
});

it('sets new path', function () {
    $stub = new Stub('/model.stub', [
        'NAME' => 'Name',
    ]);

    $stub->setPath('/new-path/');

    expect(Str::contains($stub->getPath(), 'Commands/stubs/new-path/'))->toBeTrue();
});

test('use default stub if override not exists', function () {
    $stub = new Stub('/command.stub', [
        'COMMAND_NAME' => 'my:command',
        'NAMESPACE' => 'Blog\Commands',
        'CLASS' => 'MyCommand',
    ]);

    $stub->setBasePath(__DIR__ . '/stubs');

    $stub->saveTo(base_path(), 'stub-override-not-exists.php');

    expect($this->finder->exists(base_path('stub-override-not-exists.php')))->toBeTrue();
});

test('use override stub if exists', function () {
    $stub = new Stub('/model.stub', [
        'NAME' => 'Name',
    ]);

    $stub->setBasePath(__DIR__ . '/stubs');

    $stub->saveTo(base_path(), 'stub-override-exists.php');

    expect($this->finder->exists(base_path('stub-override-exists.php')))->toBeTrue();
    expect($this->finder->get(base_path('stub-override-exists.php')))->toEqual('stub-override');
});
