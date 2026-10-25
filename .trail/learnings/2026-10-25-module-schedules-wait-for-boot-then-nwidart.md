---
title: "Module schedules wait for boot, then callAfterResolving(Schedule)"
author: Nwidart
date: 2026-10-25
branch: fix/lazy-module-schedules
cost: { minutes: 1, tokens: 2483 }
id: l-4807f8f4
scope: repo
status: active
src: agent
touches:
  - "src/Support/ModuleServiceProvider.php#registerCommandSchedules"
---

## Decided
- registerCommandSchedules() wraps callAfterResolving(Schedule::class) in app->booted(). Without the wrapper, if another provider resolved Schedule during its boot(), configureSchedules() would run inside the module's own boot(), before later providers boot. The old code always ran it after boot.
- Laravel 13 binds Schedule once as a singleton (FoundationServiceProvider::registerConsoleSchedule). afterResolving fires once per built instance, so resolving twice gives no duplicate events, and a forgotten/rebuilt instance gets the events again.

## Watch out
- Testbench's app is already booted, so "resolved during boot by another provider" cannot be simulated in package tests. QA covered it with app(Schedule::class) in AppServiceProvider::boot().
