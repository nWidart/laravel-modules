---
title: "Generators take the file path, the class name and the namespace from one StudlyCase path, so nested names give classes PSR-4 can load"
author: wadday
date: 2026-11-28
branch: fix/generator-nested-names
id: l-ae9e85dc
scope: repo
status: active
src: agent
touches:
  - src/Commands/Make/GeneratorCommand.php
  - src/Commands/Make/FactoryMakeCommand.php
  - src/Commands/Make/ObserverMakeCommand.php
  - src/Commands/Make/RuleMakeCommand.php
  - src/Commands/Make/SeedMakeCommand.php
  - src/Commands/Make/HealthIndicatorMakeCommand.php
  - tests/Commands/Make/GeneratorNestedNameTest.php
---

## Decided
- `GeneratorCommand::getClassPath()` is the name argument with `\` turned into `/` and each segment StudlyCased (`studly_path()`, which also drops empty segments). `getClass()` is its basename, `getClassFolder()` the part before the last `/`, and `getClassNamespace()` is built from that folder. Generators call `getClassPath()` where they called `Str::studly($this->argument(...))` on the whole name, which only uppercased the first character (`api/v2/x` gave folder `Api/v2`, namespace `Api\V2`), and the old `getClassNamespace()` removed the class name with `str_replace` everywhere in the name (`Payments/Payments` lost its folder).
- `getClass()` is StudlyCase now, not the raw basename: generators that write `getFileName()` but pass `getClass()` as `CLASS` (event, job, mail and others) would otherwise name the class `payments` in `Payments.php`.
- `CLASS` or `NAME` must be a basename. Rule (`getFileName()`), seeder (`getSeederName()`), factory and observer passed the whole path, which wrote `class Api/V2/Payments`. Factory and observer also add the folder to the model namespace: `Admin/Post` refers to `Models\Admin\Post`, where `module:make-model Admin/Post` puts it.
- `module:make-migration` keeps `Str::studly()` and its own `getClass()`: a migration name is a file name, not a class path. View, component view, config and the Inertia generators do not name classes. Route and event providers are named by the module argument.
- `GeneratorNestedNameTest` runs every class generator (from `ConsoleServiceProvider::defaultCommands()`, minus a short list of generators whose argument does not name a class) with `api/v2/Payments`, `Payments/Payments` and `api\v2\payments`. It parses each new PHP file with `PhpToken::tokenize(..., TOKEN_PARSE)` and requires the file path to be the one the module's `composer.json` PSR-4 map gives for the declared class. On master 33 of 34 class generators failed it; only health indicator passed. A new generator is covered without changes; one that writes no class goes on the list.

## Watch out
- The suite runs on macOS's case-insensitive filesystem too, where `Api/v2` and `Api/V2` are the same folder. The test compares path strings, so it still catches a case mismatch there.
- One existing snapshot (`ClassMakeCommandTest` sub-namespace) had recorded `class Api\Demo`, which is not valid PHP; it now reads `class Demo`.
