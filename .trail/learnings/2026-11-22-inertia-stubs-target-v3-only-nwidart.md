---
title: "Inertia stubs are v3 only; app.js mounts only with script page data"
author: Nwidart
date: 2026-11-22
branch: fix/inertia-latest
cost: { minutes: 30, tokens: 60000 }
id: l-7d2b4e19
scope: repo
status: active
src: agent
touches:
  - src/Traits/ResolvesInertiaFrontend.php
  - src/Commands/stubs/inertia/app-vue.stub
  - src/Commands/stubs/inertia/app-react.stub
  - src/Commands/stubs/inertia/app-svelte.stub
---

## Decided
- The Inertia stubs target Inertia v3 only (`inertiajs/inertia-laravel` ^3, `@inertiajs/*` ^3). Inertia v2 is not supported.
- Inertia v3 core reads page data only from `script[data-page="<id>"][type="application/json"]`. Inertia v2 read the `data-page` attribute of the root element.
- `createInertiaApp` crashes without page data, so each `app.js` stub mounts only when `<div id="app">` and that script tag exist. A plain Blade page that loads the file is skipped.
- `@inertiajs/svelte` v3 needs Svelte 5, so the Svelte stub uses `mount(App, { target: el, props })`.
- The framework flag logic lives in `Traits\ResolvesInertiaFrontend`. More than one of `--vue`, `--react`, `--svelte` fails with exit code 1 and writes nothing; `module:publish-inertia` checks this before the overwrite check.

## Watch out
- Only a Vue `app.js` published earlier keeps working. React and Svelte files must be published again with `--react --force` / `--svelte --force`.
- The React `app.js` stub has no JSX: it mounts with `createRoot(el).render(createElement(App, props))`, because the file is `resources/js/app.js` and Vite parses JSX only in `.jsx` files (the old stub failed `npm run build` with `Unexpected JSX expression`).
