---
title: "--dry-run on generators: one Support\\GeneratorRun bound in the container, every write through FileGenerator, planned modules found by FileRepository::find()"
author: Nwidart
date: 2026-11-23
branch: feat/generator-dry-run
id: l-generator-dry-run
scope: repo
status: active
src: agent
touches:
  - src/Support/GeneratorRun.php
  - src/Traits/PlansGeneratorRun.php
  - src/Generators/FileGenerator.php
  - src/Generators/ModuleGenerator.php
  - src/FileRepository.php
  - tests/Commands/Make/GeneratorDryRunTest.php
---

## Decided
- `Traits\PlansGeneratorRun` (used by `GeneratorCommand` and `ModuleMakeCommand`) adds `--dry-run` and `--json` in the constructor and overrides `execute()`, not `handle()`: subclasses such as `ModelMakeCommand` call `parent::handle()` and then other generators, so only `execute()` wraps the whole command.
- The outermost generator binds a `Support\GeneratorRun` with `$this->laravel->instance()` and forgets it in `finally`. Generators called with `$this->call()` get no `--dry-run`; they find the run with `GeneratorRun::current()` and record into it. Only the outermost one prints (list or one JSON document).
- Every file write goes through `FileGenerator::generate()`: `GeneratorCommand` already used it, and `ModuleGenerator::writeFile()` now uses it with overwrite on for `.gitkeep`, `module.json` and the stub files. `generate()` records `create`/`replace`/`keep` in the run and, in a dry run, does not call `put()`. Directory creation is skipped in a dry run at the two call sites. P5 (`--json` after a real run) can bind a run with `dryRun: false` and get the same entries.
- A module that `module:make --dry-run` would create is recorded with `GeneratorRun::planModule(name, path)`. `FileRepository::find()` checks `findPlanned()` first and builds the module with `createModule()`, so `findOrFail()`, `getModulePath()`, `ModuleCommandTrait::getModuleName()` and `validateModuleArgument()` in the sub-generators all see it. The path comes from `getModulePath()` before planning, the same fallback the real run uses. No path logic is copied.
- `--force` on an existing module records a `delete` entry for the folder; files under it then get `action: create` with `exists: true` (it is on disk now, but deleted first).
- During a dry run the command's output is swapped to a `NullOutput` (task lines and "created successfully" would be false). Errors are recorded with `reportError()` (GeneratorCommand, ModuleGenerator, ModuleMakeCommand) or `GeneratorRun::current()?->addError()` (ResolvesInertiaFrontend) and printed after the list.
- An option the command declares itself keeps its own meaning: `PlansGeneratorRun` only acts on the options it added (see the `json-test:generator` command in `WritesJsonOutputTest`).

## Watch out
- `WritesJsonOutput::inJsonMode()` is true only while `$this->output` is the style `enterJsonMode()` set (so a reused command instance starts each run fresh). `PlansGeneratorRun` swaps `$this->output` to a `NullOutput` style during a dry run and puts the JSON style back before `writeJson()`; restore it before you check `inJsonMode()`.
- Migration file names use `date()`, so a dry run and the real run a second later can differ; the tests compare paths with the date replaced.
- `module:make-event-provider` and `module:route-provider` already exist after `module:make`, so the all-generators test passes `--force` to them.
