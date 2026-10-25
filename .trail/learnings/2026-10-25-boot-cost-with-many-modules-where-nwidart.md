---
title: "Boot cost with many modules: where time goes and what was ruled out"
author: Nwidart
date: 2026-10-25
branch: master
id: l-869d3998
scope: repo
status: active
src: agent
touches:
  - src/FileRepository.php
  - src/ModuleManifest.php
  - src/Support/ModuleServiceProvider.php
  - src/Commands/stubs/event-provider.stub
---

## Decided
- Module scan is cached per FileRepository/ModuleManifest instance, also in tests (#2231). The old static cache skipped tests, so module_path() in every module provider re-read every module.json: O(N^2) per boot (3.3 s per test at 200 modules, now 249 ms).
- Maintainer chose "read module.json once, no cache file": no module:cache command, no bootstrap cache of module data. Extra saving of a cache file was ~2 ms at 200 modules, not worth the stale-cache risk.
- Module providers skip the config dir walk on web requests with cached config and register publishes() only in the console (#2230).
- 12.x gets security fixes only: no performance or bug-fix backports.

## Tried and ruled out
- composer dump-autoload -o: no measurable gain; apps already set optimize-autoloader.
- Module EventServiceProvider stub: `$shouldDiscoverEvents = true` does nothing, Laravel's shouldDiscoverEvents() requires get_class($this) === __CLASS__. The provider is empty overhead.

## Watch out
- At 200 modules with `php artisan optimize`, a request is ~77 ms vs ~32 ms with none. Most of the rest is Laravel booting 3 providers per module. Dropping the generated Event and Route providers saved 12.6 ms (16%); it changes module:make output, so it awaits a maintainer decision.
- Benchmarks: run with XDEBUG_MODE=off (Xdebug is loaded locally) and opcache.enable_cli=1 + opcache.file_cache. The workspace apps' post-autoload-dump deletes bootstrap/cache/config.php: re-run optimize after composer dump-autoload.
- Fake a web request in Testbench with $_SERVER['APP_RUNNING_IN_CONSOLE'] = 'false' before the app is created (Laravel reads it once).
