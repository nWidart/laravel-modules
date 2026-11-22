---
title: "Actuator endpoint `modules`: fixed payload, registered from the provider, no own config"
author: Nwidart
date: 2026-11-22
branch: feat/actuator-modules-endpoint
cost: { minutes: 60, tokens: 120000 }
id: l-3c9e5a72
scope: repo
status: active
src: agent
touches:
  - src/Actuator/ModulesEndpoint.php
  - src/LaravelModulesServiceProvider.php
  - composer.json
  - docs/src/content/docs/guides/production/actuator-modules-endpoint.mdx
---

## Decided
- The endpoint id is `modules`. A single module is `/actuator/modules/{name}`; the discovery link is `modules-name`.
- The payload is a list with a `name` field, not a map keyed by name. The single-module response is one element of that list.
- A module is identified in actuator output by `Module::getName()` verbatim, the same value the health indicator details use.
- The payload is a fixed allowlist of six fields (`name`, `enabled`, `version`, `description`, `priority`, `providers`). Never a `module.json` passthrough, never paths.
- laravel-modules has no config key for it. Exposure and middleware are the actuator's: `actuator.exposure` and `actuator.modules.middleware`.
- `registerActuatorEndpoint()` runs in `register()` behind `class_exists(\Nwidart\Actuator\Actuator::class)`. `register()` does not depend on provider boot order. That class is absent in actuator 1.0.0, so 1.0.0 and "not installed" take the same no-op path and no `conflict` entry is needed.
- `Actuator::endpoint()` throws on a duplicate id, and the registry is a container singleton per application. A forced re-register of the provider on the same app would throw, so `registerActuatorEndpoint()` returns early when `$this->app->getProviders(self::class) !== []`.
- `require-dev` pins `nwidart/laravel-actuator` to `^1.1`: custom endpoints shipped in actuator 1.1.0. The branch was first built against `dev-master` before the tag existed. `suggest` lists it for users.
- `modules.actuator.enabled` controls the health indicators only. It deliberately does not gate the endpoint: `registerActuatorEndpoint()` does not read it, and exposure is the actuator's own `actuator.exposure` and middleware.

## Watch out
- Adding a field to the payload is a security decision: keep the allowlist, and update the docs page and the Boost skill.
