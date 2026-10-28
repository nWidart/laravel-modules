---
title: "Generator name validation lives in GeneratorCommand::validateNameArgument; only seeders get a base-class clash guard"
author: Nwidart
date: 2026-10-28
branch: fix/make-empty-name
cost: { minutes: 10, tokens: 62771 }
id: l-62c53887
scope: repo
status: active
src: agent
touches:
  - src/Commands/Make/GeneratorCommand.php
  - src/Commands/Make/MigrationMakeCommand.php
  - src/Commands/Make/SeedMakeCommand.php
  - tests/Commands/Make/GeneratorCommandTest.php
  - tests/Commands/Make/SeedMakeCommandTest.php
---

## Decided
- Empty/whitespace names for every module:make-* generator are rejected in `src/Commands/Make/GeneratorCommand.php` `validateNameArgument()`, not per command. A generator only gets validated if it sets `$argumentName`; `MigrationMakeCommand` lacked it until #2246 and skipped all name checks.
- For providers whose `$argumentName` is `module` (RouteProvider, EventProvider), an explicit `""` now errors; an omitted (null) module still falls back to `module:use`.
- `SeedMakeCommand` overrides `validateNameArgument()` to reject names resolving to class `Seeder`, because `getSeederName()` appends the suffix and the stub extends `Illuminate\Database\Seeder`.

## Tried and ruled out
- Generic base-class clash guards (e.g. `module:make-command Command` -> `class Command extends Command`): not added. Laravel's own make:* commands do not guard these either.

## Watch out
- Names that are not valid PHP class names (`module:make-seed 0 Blog`, `Seeder.php`) still generate broken files; no class-name validation exists yet.
- `module:make` (ModuleMakeCommand) is not a GeneratorCommand; it has its own regex check and already rejects "".
