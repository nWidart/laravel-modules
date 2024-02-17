<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Facades\Module;

it('resolves the module facade', function () {
    $modules = Module::all();

    expect(is_array($modules))->toBeTrue();
});

it('creates macros via facade', function () {
    $modules = Module::macro('testMacro', function () {
        return true;
    });

    expect(Module::hasMacro('testMacro'))->toBeTrue();
});

it('calls macros via facade', function () {
    $modules = Module::macro('testMacro', function () {
        return 'a value';
    });

    expect(Module::testMacro())->toEqual('a value');
});
