<?php

uses(\Nwidart\Modules\Tests\BaseTestCase::class);
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Json;

beforeEach(function () {
    $path = __DIR__ . '/stubs/valid/module.json';
    $this->json = new Json($path, $this->app['files']);
});

it('gets the file path', function () {
    $path = __DIR__ . '/stubs/valid/module.json';

    expect($this->json->getPath())->toEqual($path);
});

it('throws an exception with invalid json', function () {
    $path = __DIR__ . '/stubs/InvalidJsonModule/module.json';

    $this->expectException(InvalidJsonException::class);
    $this->expectExceptionMessage('Error processing file: ' . $path . '. Error: Syntax error');

    new Json($path, $this->app['files']);
});

it('gets attributes from json file', function () {
    expect($this->json->get('name'))->toEqual('Order');
    expect($this->json->get('alias'))->toEqual('order');
    expect($this->json->get('description'))->toEqual('My demo module');
    expect($this->json->get('version'))->toEqual('0.1');
    expect($this->json->get('keywords'))->toEqual(['my', 'stub', 'module']);
    expect($this->json->get('active'))->toEqual(1);
    expect($this->json->get('order'))->toEqual(1);
});

it('reads attributes from magic get method', function () {
    expect($this->json->name)->toEqual('Order');
    expect($this->json->alias)->toEqual('order');
    expect($this->json->description)->toEqual('My demo module');
    expect($this->json->version)->toEqual('0.1');
    expect($this->json->keywords)->toEqual(['my', 'stub', 'module']);
    expect($this->json->active)->toEqual(1);
    expect($this->json->order)->toEqual(1);
});

it('makes json class', function () {
    $path = __DIR__ . '/stubs/valid/module.json';
    $json = Json::make($path, $this->app['files']);

    expect($json)->toBeInstanceOf(Json::class);
});

it('sets a path', function () {
    $path = __DIR__ . '/stubs/valid/module.json';
    expect($this->json->getPath())->toEqual($path);

    $this->json->setPath('some/path.json');
    expect($this->json->getPath())->toEqual('some/path.json');
});

it('decodes json', function () {
    $expected = '{
    "name": "Order",
    "alias": "order",
    "description": "My demo module",
    "version": "0.1",
    "keywords": [
        "my",
        "stub",
        "module"
    ],
    "active": 1,
    "order": 1,
    "providers": [
        "Modules\\\Order\\\Providers\\\OrderServiceProvider",
        "Modules\\\Order\\\Providers\\\EventServiceProvider",
        "Modules\\\Order\\\Providers\\\RouteServiceProvider"
    ],
    "aliases": [],
    "files": []
}';
    expect($this->json->toJsonPretty())->toEqual($expected);
});

it('sets a key value', function () {
    $this->json->set('key', 'value');

    expect($this->json->get('key'))->toEqual('value');
});

it('can be casted to string', function () {
    $expected = '{
    "name": "Order",
    "alias": "order",
    "description": "My demo module",
    "version": "0.1",
    "keywords": [
        "my",
        "stub",
        "module"
    ],
    "active": 1,
    "order": 1,
    "providers": [
        "Modules\\\Order\\\Providers\\\OrderServiceProvider",
        "Modules\\\Order\\\Providers\\\EventServiceProvider",
        "Modules\\\Order\\\Providers\\\RouteServiceProvider"
    ],
    "aliases":{},
    "files": [
    ]
}
';
    expect((string)$this->json)->toEqual($expected);
});
