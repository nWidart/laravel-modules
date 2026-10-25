---
title: "phpstan level 5 with Larastan, Lumen and ignore rules"
author: Nwidart
date: 2026-10-25
branch: chore/phpstan-level-5
cost: { minutes: 1, tokens: 1867 }
id: l-70f7ed0e
scope: repo
status: active
replaces: l-26b5769f
src: agent
touches:
  - phpstan.neon.dist
  - composer.json
  - src/FileRepository.php
  - src/Module.php
  - src/ModuleManifest.php
  - src/Migrations/Migrator.php
  - src/Traits/MigrationLoaderTrait.php
---

## Decided
- phpstan.neon.dist runs Larastan (larastan/larastan ^3.9.3, the first release allowing Laravel 13; it pulls phpstan/phpstan, so there is no direct phpstan requirement) at level 5 on src/, with no baseline. This supersedes the phpstan part of the 2026-10-25 dev-scripts learning: its level counts (master a6b3ba6) are stale, SeedCommand has()/Migrator whereMigration() were fixed in #2215, and getCachedServicesPath() on ModulesServiceProvider is no longer reported.
- Lumen: the package supports both Laravel and Lumen, but Lumen must never be installed alongside Laravel (no laravel/lumen-framework or Lumen stubs in require-dev). PHPStan therefore cannot see Laravel\Lumen\Application; one ignoreErrors entry (identifier class.notFound, message #Laravel\\Lumen\\#) covers it. Maintainer decision.
- Only two ignores exist: that Lumen one and trait.unused for src/Traits/MigrationLoaderTrait.php (deprecated, kept for BC, removed in v14; PHPStan has no setting to stop reporting unused traits). New errors get fixed; a baseline or extra ignore needs maintainer approval.
- FileRepository::$app and Module::$app are typed in docblocks as (Container&Contracts\Foundation\Application)|(Container&Lumen Application): the native constructor type stays Container for BC, but the classes call application methods on it.
- Where Laravel's docblocks are wrong, use an inline @var on a local with a one-line comment, not runtime rewrites: Connection::getSchemaGrammar() can return null (Migrator keeps its is_null check), Collection::collapse() keeps string keys (ModuleManifest::getAliases()), Filesystem::glob() can return false.

## Tried and ruled out
- Larastan as a way to lower error counts: it barely changes levels 2-8 (level 9 drops from ~991 to ~900). It is in for correctness of Laravel types, not for a quick level bump.
- Narrowing native return types of protected/public get*Name(): array|string methods in Make commands: BC break for user subclasses that override them. Only the private getClassNameWithoutNamespace() methods were narrowed to string.

## Watch out
- Level 6 has about 287 errors, mostly missingType.iterableValue/parameter/return.
- PHPStan sometimes crashes at the default 128M memory limit; run with --memory-limit=1G locally if it does.
- FileRepository::scan() uses (array) glob(...), which turns a false return into [false]; ModuleManifest guards this, scan() does not. Not fixed yet (behaviour change).
- The test suite runs a real composer dump-autoload in the Testbench skeleton, whose vendor is the package vendor; after a full run the next phpunit run can fail with Class "Nwidart\Modules\Tests\BaseTestCase" not found until composer dump-autoload runs in the package root.
