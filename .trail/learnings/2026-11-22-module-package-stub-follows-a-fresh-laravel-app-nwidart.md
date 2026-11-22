---
title: The module package.json stub follows a fresh Laravel app; the vite stub writes manifest.json
author: Nwidart
date: 2026-11-22
branch: fix/module-package-vite8
cost: { minutes: 20, tokens: 40000 }
id: l-7b2e4c18
scope: repo
status: active
src: agent
touches:
  - src/Commands/stubs/package.stub
  - src/Commands/stubs/vite.stub
---

## Decided
- `package.stub` uses the constraints of a fresh Laravel 13 app: `vite ^8.0.0`, `laravel-vite-plugin ^3.1`, `sass ^1.70.0`. A standalone module build then runs the same Vite as the root.
- `vite.stub` sets `build.manifest: 'manifest.json'`. Vite 5+ writes `manifest: true` to `<outDir>/.vite/manifest.json`, while Laravel's `Vite` class and `module_vite()` read `<buildDirectory>/manifest.json`.

## Watch out
- Bump both stubs together at the next Laravel major.
- Only new modules change. An existing module keeps its files and must switch its `manifest` line by hand when it moves to Vite 5+. The Vite guide says so.
