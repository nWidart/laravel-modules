<?php

namespace Nwidart\Modules\tests;

use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Json;

class JsonTest extends BaseTestCase
{
    /**
     * @var Json
     */
    private $json;

    public function setUp()
    {
        parent::setUp();
        $path = __DIR__ . '/stubs/module.json';
        $this->json = new Json($path, $this->app['files']);
    }

    /** @test */
    public function it_gets_the_file_path()
    {
        $path = __DIR__ . '/stubs/module.json';

        $this->assertEquals($path, $this->json->getPath());
    }

    /** @test */
    public function it_throws_an_exception_with_invalid_json()
    {
        $path = __DIR__ . '/stubs/InvalidJsonModule/module.json';

        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Error processing file: ' . $path . '. Error: Syntax error');

        new Json($path, $this->app['files']);
    }

    /** @test */
    public function it_gets_attributes_from_json_file()
    {
        $this->assertEquals('Order', $this->json->get('name'));
        $this->assertEquals('order', $this->json->get('alias'));
        $this->assertEquals('My demo module', $this->json->get('description'));
        $this->assertEquals('0.1', $this->json->get('version'));
        $this->assertEquals(['my', 'stub', 'module'], $this->json->get('keywords'));
        $this->assertEquals(1, $this->json->get('active'));
        $this->assertEquals(1, $this->json->get('order'));
    }

    /** @test */
    public function it_reads_attributes_from_magic_get_method()
    {
        $this->assertEquals('Order', $this->json->name);
        $this->assertEquals('order', $this->json->alias);
        $this->assertEquals('My demo module', $this->json->description);
        $this->assertEquals('0.1', $this->json->version);
        $this->assertEquals(['my', 'stub', 'module'], $this->json->keywords);
        $this->assertEquals(1, $this->json->active);
        $this->assertEquals(1, $this->json->order);
    }

    /** @test */
    public function it_makes_json_class()
    {
        $path = __DIR__ . '/stubs/module.json';
        $json = Json::make($path, $this->app['files']);

        $this->assertInstanceOf(Json::class, $json);
    }

    /** @test */
    public function it_sets_a_path()
    {
        $path = __DIR__ . '/stubs/module.json';
        $this->assertEquals($path, $this->json->getPath());

        $this->json->setPath('some/path.json');
        $this->assertEquals('some/path.json', $this->json->getPath());
    }

    /** @test */
    public function it_decodes_json()
    {
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
        $this->assertEquals($expected, $this->json->toJsonPretty());
    }

    /** @test */
    public function it_sets_a_key_value()
    {
        $this->json->set('key', 'value');

        $this->assertEquals('value', $this->json->get('key'));
    }

    /** @test */
    public function it_can_be_casted_to_string()
    {
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
        $this->assertEquals($expected, (string)$this->json);
    }
}
