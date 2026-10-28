---
title: "PHP 8.3 idioms: what stays untyped until v14 and why"
author: Nwidart
date: 2026-10-27
branch: feat/php83-idioms
cost: { minutes: 23, tokens: 73008 }
id: l-4a7cdd5b
scope: repo
status: active
src: agent
touches:
  - phpstan.neon.dist
---

## Decided
- Package floor is php ^8.3 (CI 8.3-8.5): no 8.4 features (property hooks, asymmetric visibility, array_find/any/all, new without parens) until the floor moves in a major.
- #[\Override] is on every overriding method in src/, including src/Lumen/ (parents there are package classes). phpstan.neon.dist enforces it with checkMissingOverrideMethodAttribute: true. New overriding methods must carry it.
- ModuleEvent constants are typed `const string`; BaseCommand::ALL and ModelPruneCommand::ALL stay untyped.
- Only private properties got types/readonly (GeneratorPath, CheckLangCommand::$langPath).

## Tried and ruled out
- Typing BaseCommand::ALL: a user subclass that redeclares ALL untyped fatals ("Type of C::ALL must be compatible with P::ALL of type string"). BaseCommand is the documented base for user commands.
- Typing command $name/$description/$signature: inherited untyped from Illuminate Command, typing them fatals. $argumentName (GeneratorCommand) and protected props of Module, FileRepository, Migrator: typing breaks user subclasses that redeclare them; v14 only.
- Arrow fn for the BaseCommand multisearch closure: Pint reads `Collection &$modules` in an fn as bitwise AND, deletes the Collection import, PHPStan then fails. Keep it a closure.

## Watch out
- switch -> match is strict: ListCommand `--only` called programmatically with `true` used to list enabled modules, now lists all. CLI always passes string|null.
- Full test run can leave the autoloader broken ("Class Nwidart\Modules\Tests\BaseTestCase not found"): run composer dump-autoload and rerun.
