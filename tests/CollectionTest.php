<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Collection;
use Nwidart\Modules\Laravel\Module;

test('to array sets path attribute', function () {
    $moduleOnePath = __DIR__ . '/stubs/valid/Recipe';
    $moduleTwoPath = __DIR__ . '/stubs/valid/Requirement';
    $modules = [
        new Module($this->app, 'module-one', $moduleOnePath),
        new Module($this->app, 'module-two', $moduleTwoPath),
    ];
    $collection = new Collection($modules);
    $collectionArray = $collection->toArray();

    expect($collectionArray[0])->toHaveKey('path');
    expect($collectionArray[0]['path'])->toEqual($moduleOnePath);
    expect($collectionArray[1])->toHaveKey('path');
    expect($collectionArray[1]['path'])->toEqual($moduleTwoPath);
});

test('get items returns the collection items', function () {
    $modules = [
        new Module($this->app, 'module-one', __DIR__ . '/stubs/valid/Recipe'),
        new Module($this->app, 'module-two', __DIR__ . '/stubs/valid/Requirement'),
    ];
    $collection = new Collection($modules);
    $items = $collection->getItems();

    expect($items)->toHaveCount(2);
    expect($items[0])->toBeInstanceOf(Module::class);
});
