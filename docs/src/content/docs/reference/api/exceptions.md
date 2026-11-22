---
title: Exceptions
description: The exception classes of Nwidart\Modules\Exceptions, with the code that throws each one and when.
---

The package defines five exception classes in `Nwidart\Modules\Exceptions`. Each extends `\Exception`. None of them adds a property or a method, except the static factory methods that `InvalidActivatorClass` and `InvalidAssetPath` provide.

| Class | Thrown by | When |
| --- | --- | --- |
| [`ModuleNotFoundException`](#modulenotfoundexception) | `FileRepository::findOrFail()` and every method that calls it | A module does not exist. |
| [`InvalidJsonException`](#invalidjsonexception) | `Json`, `FileActivator` | A JSON file is not valid. |
| [`InvalidActivatorClass`](#invalidactivatorclass) | The binding of `ActivatorInterface` | The activator configuration is not valid. |
| [`InvalidAssetPath`](#invalidassetpath) | `FileRepository::asset()` | The asset name has no module name. |
| [`FileAlreadyExistException`](#filealreadyexistexception) | `Generators\FileGenerator::generate()` | The file exists and overwriting is off. |

Each exception has the same fields: the code that throws it, the condition, the message and an example.

## ModuleNotFoundException

| Field | Value |
| --- | --- |
| Thrown by | `FileRepository::findOrFail()`. Callers pass it on: `isEnabled()`, `isDisabled()`, `enable()`, `disable()`, `delete()`, `setUsed()` and `getUsedNow()` of the repository, `module_path()`, `GeneratorCommand::module()` and `ModuleCommandTrait::getModuleName()`. |
| Thrown when | A module does not exist. `getUsedNow()` throws when no module is used (the name is empty). Commands do not let it escape: `BaseCommand`, `module:prune` and the generators check the module names first, print `Module [<name>] does not exist.` and exit with `1`. `Artisan::call()` returns `1` instead of throwing. |
| Message | `Module [<name>] does not exist!` |

Two callers catch it: `module()` returns `false`, and `FileRepository::getModulePath()` returns the path the module would have. `find()` and `has()` never throw it.

```php
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Facades\Module;

try {
    Module::findOrFail('Missing');
} catch (ModuleNotFoundException $e) {
    // Module [Missing] does not exist!
}
```

## InvalidJsonException

| Field | Value |
| --- | --- |
| Thrown by | `Json::decodeContents()`, and so `new Json()`, `Json::make()` and the reading of `module.json` and `composer.json`. The `FileActivator` constructor. |
| Thrown when | A file cannot be decoded as JSON. For `Json`: the content is not valid JSON, including an empty file. For `FileActivator`: the statuses file is not valid JSON, is not an object, or is a non-empty list. The activator never treats such a file as empty, because the next write would replace it. |
| Message | `Json`: `Error processing file: <path>. Error: <json error>`. `FileActivator`: `The module statuses file [<path>] is not valid: <error>. Fix or restore the file (it maps module names to true or false).` |

A `module.json` that is not valid makes `scan()` throw, so every repository call that scans throws it.

```php
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Json;

try {
    Json::make(module_path('Blog', 'module.json'));
} catch (InvalidJsonException $e) {
    // Error processing file: <path>. Error: <json error>
}
```

## InvalidActivatorClass

| Field | Value |
| --- | --- |
| Thrown by | The container binding of `ActivatorInterface`, through three static factory methods. |
| Thrown when | `unknownActivator(mixed $activator)`: `modules.activator` is not a string, `modules.activators` is not an array, or the activator is not a key of it. `missingConfig()`: the entry of the activator has no `class` key. `classNotFound(string $activator, string $class)`: the `class` does not exist. |
| Message | `unknownActivator()`: starts with `The modules activator [<name>] is not defined.` `missingConfig()`: starts with `You don't have a valid activator configuration class.` and tells you to republish the config. `classNotFound()`: starts with `The class [<class>] of the modules activator [<activator>] does not exist.` |

```php
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;

config(['modules.activator' => 'database']);

try {
    app(ActivatorInterface::class);
} catch (InvalidActivatorClass $e) {
    // The modules activator [database] is not defined. ...
}
```

## InvalidAssetPath

| Field | Value |
| --- | --- |
| Thrown by | `FileRepository::asset()`, through the static factory method `missingModuleName($asset)`. |
| Thrown when | The asset has no `:`. |
| Message | `Module name was not specified in asset [<asset>].` |

```php
Module::asset('css/app.css'); // throws InvalidAssetPath
Module::asset('blog:css/app.css'); // //example.test/modules/blog/css/app.css
```

## FileAlreadyExistException

| Field | Value |
| --- | --- |
| Thrown by | `Generators\FileGenerator::generate()`. |
| Thrown when | The file exists and `withFileOverwrite(true)` was not called. |
| Message | `File already exists!` |

`GeneratorCommand::handle()` catches it, prints `File : <path> already exists.` and returns `1`. A generator command overwrites the file with `--force`.

```php
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Generators\FileGenerator;

try {
    (new FileGenerator($path, $contents))->generate();
} catch (FileAlreadyExistException $e) {
    // File already exists!
}
```
