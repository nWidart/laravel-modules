---
title: "Module EventServiceProvider stub never discovers listeners; discovery is opt-in through the application's paths"
author: Nwidart
date: 2026-11-22
branch: fix/event-discovery-stub
id: l-3d7e51a9
scope: repo
status: active
src: agent
touches:
  - src/Commands/stubs/event-provider.stub
  - src/Support/ListenerClassNameGuesser.php
  - tests/Support/ListenerClassNameGuesserTest.php
---

## Decided
- `protected static $shouldDiscoverEvents = true;` is removed from `src/Commands/stubs/event-provider.stub`. It never had an effect: Laravel's `shouldDiscoverEvents()` returns true only for the framework class itself, never for a subclass. This supersedes the 2026-10-25 note, which says the stub has the property.
- A module `EventServiceProvider` registers only what its `$listen` array lists. The stub docblocks say so.
- Module listeners are discovered only if the application opts in: `->withEvents(discover: [__DIR__.'/../app/Listeners', __DIR__.'/../Modules/*/app/Listeners'])` in `bootstrap/app.php`, or `EventServiceProvider::addEventDiscoveryPaths()`. `Support\ListenerClassNameGuesser` maps `Modules/Blog/app/Listeners/SyncPoints.php` to `Modules\Blog\Listeners\SyncPoints`.
- The empty `configureEmailVerification()` override stays: without it each module provider registers `SendEmailVerificationNotification` on `Registered` again, one extra verification email per module.

## Tried and ruled out
- Automatic discovery of module listeners: not added. A listener both discovered and listed in a module `$listen` is registered twice, so it needs an opt-in flag.

## Watch out
- The glob matches disabled modules. After enabling or disabling a module, re-run `php artisan event:cache`.
