---
title: "Module tag on the actuator's scheduledtasks: claim the events configureSchedules() adds"
author: Nwidart
date: 2026-11-26
branch: feat/actuator-task-module
id: l-5d1f0a3e
scope: repo
status: active
src: agent
touches:
  - src/Actuator/ScheduledTaskModules.php
  - src/Support/ModuleServiceProvider.php#registerCommandSchedules
  - src/LaravelModulesServiceProvider.php#registerActuatorEndpoint
---

## Decided
- The module of a task goes on `scheduledtasks` as `tags.module`, not on the `modules` endpoint. Mortise may ask for `scheduledtasks` but not for `modules`.
- `registerCommandSchedules()` counts the Schedule's protected `$events` (read through a closure bound to the Schedule) before `configureSchedules()` and `array_slice`s the new ones; events are appended, so the slice is exact. Not `events()`: with `Schedule::$alwaysOnOneServer` on, it calls `onOneServer()` on every event, for good.
- The claim uses the `module.json` name (`RepositoryInterface::find($this->name)->getName()`, resolved in the callback), as the `modules` endpoint reports it, and falls back to `$this->name` when the repository is unbound or has no such module. The repository lookup is case-insensitive.
- `ScheduledTaskModules::claim()` keeps the first claim: a module provider registered inside another module's `configureSchedules()` claims its events before the outer slice reaches them.
- `ScheduledTaskModules` holds the events in a `WeakMap`: a rebuilt Schedule leaves no stale entry, and the module provider re-claims the new instance's events.
- The tagger is registered in `registerActuatorEndpoint()` behind `ReflectionClass::hasMethod('tagScheduledTasksUsing')`, after the duplicate-register guard. Older actuators take the no-op path. `method_exists()` and `is_callable()` fail PHPStan: it sees one installed actuator and calls the check always true or always false.
- A task scheduled in `routes/console.php` gets no tag, even when it runs a module command. Consumers may fall back to matching the command.
- `modules.actuator.enabled` does not gate the tag, like the endpoint.

## Watch out
- A module provider can be registered without the package provider (tests do this), so the claim site uses `singletonIf` before `make`.
- PHPStan and `ScheduledTasksTagTest` need an actuator with `tagScheduledTasksUsing()` in `require-dev`.
