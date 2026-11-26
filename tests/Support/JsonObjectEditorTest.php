<?php

namespace Nwidart\Modules\Tests\Support;

use Nwidart\Modules\Support\JsonObjectEditor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonObjectEditorTest extends TestCase
{
    public static function setCases(): array
    {
        return [
            'appended after the last member' => [
                "{\n    \"a\": 1,\n    \"b\": [1, 2]\n}\n",
                "{\n    \"a\": 1,\n    \"b\": [1, 2],\n    \"s\": {\n        \"x\": [\n            \"y\"\n        ]\n    }\n}\n",
            ],
            'tab indent' => [
                "{\n\t\"a\": 1\n}",
                "{\n\t\"a\": 1,\n\t\"s\": {\n\t\t\"x\": [\n\t\t\t\"y\"\n\t\t]\n\t}\n}",
            ],
            'replaced in place' => [
                "{\n  \"s\": {\"old\": true},\n  \"a\": 1\n}",
                "{\n  \"s\": {\n    \"x\": [\n      \"y\"\n    ]\n  },\n  \"a\": 1\n}",
            ],
            'the last of duplicate keys replaced, as json_decode reads it' => [
                "{\n    \"s\": 1,\n    \"s\": 2\n}",
                "{\n    \"s\": 1,\n    \"s\": {\n        \"x\": [\n            \"y\"\n        ]\n    }\n}",
            ],
            'escaped key' => [
                "{\n    \"\\u0073\": null\n}",
                "{\n    \"\\u0073\": {\n        \"x\": [\n            \"y\"\n        ]\n    }\n}",
            ],
            'strings holding quotes, braces and backslashes' => [
                "{\n    \"a\": \"}\\\\\\\"{[\",\n    \"b\": {\"c\": \"]\\\\\"}\n}",
                "{\n    \"a\": \"}\\\\\\\"{[\",\n    \"b\": {\"c\": \"]\\\\\"},\n    \"s\": {\n        \"x\": [\n            \"y\"\n        ]\n    }\n}",
            ],
            'compact object' => [
                '{"a":1,"b":true}',
                '{"a":1,"b":true,"s":{"x":["y"]}}',
            ],
            'empty object' => [
                '{}',
                "{\n    \"s\": {\n        \"x\": [\n            \"y\"\n        ]\n    }\n}",
            ],
            'empty object with space' => [
                " { \n } \n",
                " {\n    \"s\": {\n        \"x\": [\n            \"y\"\n        ]\n    }\n} \n",
            ],
            'crlf' => [
                "{\r\n  \"a\": 1\r\n}\r\n",
                "{\r\n  \"a\": 1,\r\n  \"s\": {\r\n    \"x\": [\r\n      \"y\"\r\n    ]\r\n  }\r\n}\r\n",
            ],
        ];
    }

    #[DataProvider('setCases')]
    public function test_it_sets_a_member(string $json, string $expected)
    {
        $result = JsonObjectEditor::set($json, 's', ['x' => ['y']], JSON_PRETTY_PRINT);

        $this->assertSame($expected, $result);
        $this->assertSame(['x' => ['y']], json_decode($result, true)['s']);
    }

    public static function removeCases(): array
    {
        return [
            'first member' => ["{\n    \"s\": 1,\n    \"a\": 2\n}\n", "{\n    \"a\": 2\n}\n"],
            'middle member' => ["{\n    \"a\": 1,\n    \"s\": {\"b\": [2]},\n    \"c\": 3\n}", "{\n    \"a\": 1,\n    \"c\": 3\n}"],
            'last member' => ["{\n    \"a\": 1,\n    \"s\": [\"}\"]\n}\n", "{\n    \"a\": 1\n}\n"],
            'only member' => ["{\n    \"s\": 1\n}\n", "{}\n"],
            'duplicate keys' => ['{"s":1,"a":2,"s":3}', '{"a":2}'],
            'absent' => ["{\n    \"a\": 1\n}", "{\n    \"a\": 1\n}"],
            'empty object' => ['{}', '{}'],
            'nested key of the same name is kept' => ['{"a":{"s":1},"s":2}', '{"a":{"s":1}}'],
        ];
    }

    #[DataProvider('removeCases')]
    public function test_it_removes_a_member(string $json, string $expected)
    {
        $this->assertSame($expected, JsonObjectEditor::remove($json, 's'));
    }
}
