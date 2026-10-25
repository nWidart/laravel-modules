---
title: "phpunit coverage, phpstan level, dev scripts"
author: Nwidart
date: 2026-10-25
branch: chore/dev-experience
cost: { minutes: 17, tokens: 115026 }
id: l-26b5769f
scope: repo
status: active
src: agent
touches:
  - phpunit.xml.dist
  - composer.json
  - ".github/workflows/php.yml"
---

phpunit.xml.dist has no <coverage><report>: with reports in the config, any phpunit run on a machine where Xdebug is loaded but xdebug.mode lacks 'coverage' prints a runner warning and executes 0 tests (exit 1). Coverage is opt-in via 'composer test-coverage', which sets XDEBUG_MODE=coverage with @putenv so it also works when the user's Xdebug is in develop/debug mode. Do not move the reports back into phpunit.xml.dist.

phpstan.neon.dist is at level 1 because that is the highest level src/ passes with no baseline. Counts on master a6b3ba6: level 0: 8, 1: 9, 2: 24, 3: 28, 4: 54, 5: 55, 6: 355. The level-2 errors include likely real bugs (RepositoryInterface has no has() but SeedCommand calls it; Migrator calls Query\Builder::whereMigration(); ModulesServiceProvider calls getCachedServicesPath() on the Application contract) plus Lumen classes that are not installed. Raise the level only after fixing those, not with a baseline.

new static() in Json, Stub, InvalidActivatorClass, InvalidAssetPath is kept (subclasses rely on late static binding; Json::make returns static) and marked @phpstan-consistent-constructor instead of switching to new self() or final, which would be BC breaks. GeneratorCommand gets a @method getModuleName() tag rather than an abstract method, because third-party generators that never call module() without a name would break.

The full suite takes about 3 minutes with Xdebug loaded, close to Composer's 300 s process timeout, so test, test-coverage and update-snapshots call Composer\Config::disableProcessTimeout. php-cs-fixer (composer pcf) was removed: nothing but the script and .phpactor.json used it and its rules fought Pint.
