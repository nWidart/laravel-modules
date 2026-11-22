---
title: Each generated module has its own Vite hot file, storage/vite-<lower>.hot
author: Nwidart
date: 2026-11-22
branch: fix/module-vite-hot-file
cost: { minutes: 20, tokens: 40000 }
id: l-3c9d5a62
scope: repo
status: active
src: agent
touches:
  - src/Commands/stubs/vite.stub
  - src/Commands/stubs/views/master.stub
---

## Decided
- `vite.stub` sets `hotFile: path.join(appDirectory, 'storage/vite-$LOWER_NAME$.hot')`. `views/master.stub` passes `storage_path('vite-$LOWER_NAME$.hot')` as the third argument of both `module_vite()` calls.
- The plugin default `public/hot` belongs to the application's `@vite`. A module dev server that wrote it pointed the application's assets at the module's server, and `module_vite()` never saw it (it read `storage/vite.hot`).
- `module_vite()` keeps its default `storage_path('vite.hot')` for backward compatibility.

## Watch out
- `hotFile` may be absolute. `publicDirectory` may not: the plugin strips leading slashes, so it stays `'../../public'`.
- Only new modules change. An older module needs the `hotFile` line and the third argument by hand. The Vite guide says so.
- A Laravel app's `.gitignore` ignores `/public/hot`, not `storage/*.hot`. The guide suggests `/storage/*.hot`.
