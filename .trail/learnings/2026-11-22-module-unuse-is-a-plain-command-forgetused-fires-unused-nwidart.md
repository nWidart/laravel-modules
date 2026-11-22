---
title: "module:unuse is a plain command; forgetUsed() takes no argument and fires unused"
author: Nwidart
date: 2026-11-22
branch: fix/repository-fixes
cost: { minutes: 15, tokens: 40000 }
id: l-3c9e5a71
scope: repo
status: active
src: agent
touches:
  - src/Commands/Actions/UnUseCommand.php
  - src/FileRepository.php
  - src/Contracts/RepositoryInterface.php
---

## Decided
- `module:unuse` (`UnUseCommand`) extends Illuminate `Command`, not `BaseCommand`. It needs no module name and never prompts. The `module` argument and `--all` are still accepted and ignored, for backward compatibility.
- The command calls `forgetUsed()` with no argument. The `@method` tag on `RepositoryInterface` is `forgetUsed()`.
- `FileRepository::forgetUsed()` deletes the stored file, then fires the `unused` event (`ModuleEvent::UNUSED`) on the module that was in use. No event fires when nothing is in use or the stored name matches no module.
- `getUsedNow()` returns the studly-case name as a `string`.

## Tried and ruled out
- Keeping `forgetUsed($module)` for custom repositories: it kept a dead argument in `FileRepository` and made `module:unuse` fail for an unknown or removed module.

## Watch out
- Trade-off: a repository that does not extend `FileRepository` and declares a required `forgetUsed(Module $module)` parameter now gets an `ArgumentCountError` from `module:unuse`. An optional parameter still works.
- This supersedes the "Watch out" note in `2026-10-25-phpstan-level-6-modules-alias-invariant-nwidart.md` that `UnUseCommand` keeps calling `forgetUsed($module)`.
