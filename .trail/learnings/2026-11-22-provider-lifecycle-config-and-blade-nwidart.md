---
title: Package config merges first and Blade registers on compiler resolution
author: Nwidart
date: 2026-11-22
branch: fix/provider-lifecycle
id: l-8914b085
scope: repo
status: active
src: agent
touches:
  - src/LaravelModulesServiceProvider.php
  - src/Support/ModuleServiceProvider.php
---

## Decided

- Laravel's `mergeConfigFrom()` merges top-level keys. A published `auto-discover` array can omit `migrations`, so its lookup still needs the `true` default. A missing `translations` key is falsey.
- `callAfterResolving('blade.compiler', ...)` registers Blade features on the first compiler resolution and applies them immediately to a compiler that already exists.
- HTTP callers can invoke module commands through `Artisan::call()` and use the configured path through the public `Stub` API. Both paths need the package setup in HTTP applications.
