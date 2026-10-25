---
title: "PHPStan level 6: modules alias, invariant Larastan Collection, array|string docblocks"
author: Nwidart
date: 2026-10-25
branch: chore/phpstan-level-6
cost: { minutes: 14, tokens: 87943 }
id: l-0f835316
scope: repo
status: active
src: agent
touches:
  - phpstan.neon.dist
---

## Decided
- phpstan.neon.dist is at level 6 with no baseline and no new ignores.
- Commands get the module repository from `Traits\InteractsWithModuleRepository::moduleRepository()` (used by BaseCommand, GeneratorCommand and the commands that extend Illuminate Command directly). It has a docblock-only `@return RepositoryInterface`, so user subclasses that override it do not fatal. It resolves the `'modules'` key, not `RepositoryInterface::class`. `'modules'` is an alias of RepositoryInterface, so `instance('modules', ...)` (Module::swap(), Module::shouldReceive(), SeedCommandTest) removes the alias, and `make(RepositoryInterface::class)` would still return the real repository. Resolving the class key made SeedCommandTest::test_get_module_by_name_only_uses_methods_of_the_repository_interface fail.
- Other services use `->make(Class::class)` (Filesystem, Contracts\Config\Repository, DatabaseManager, Contracts\Events\Dispatcher, UrlGenerator). Those classes are aliases of the string keys ('files', 'config', ...), so facade swaps by string key still reach them. Laravel's migrator is the exception, in Migrations\Migrator::laravelMigrator(): it is resolved as 'migrator', because Migrator::class is a non-shared bind and would re-fire the Migrator resolving callbacks on every call.
- Public traits that users may put in their own commands (ModuleCommandTrait, CanClearModulesCache) and helpers.php keep resolving `'modules'`, with a local `@var RepositoryInterface`. They cannot rely on moduleRepository().
- RepositoryInterface has `@method` tags for setUsed, forgetUsed, getUsedNow, update and resetModules. The commands call these on the bound repository; adding them as real interface methods would break custom implementations.

## Tried and ruled out
- `@return string` on a method with native `array|string`: PHPStan still reports missingType.iterableValue for the native `array`. The docblock has to keep an array member (`string|array<int, string>`).
- Narrowing `Collection<int, string>` returns: Larastan's Collection stub declares TValue invariant, not covariant, so `Collection<int, class-string>` or `Collection<int, non-falsy-string>` is rejected. Closure return types and `@return` on closures do not change what is inferred. Declare the exact inferred type (ModuleManifest uses `non-empty-array<string, mixed>`), or use `@phpstan-return Collection<int, covariant string>` (ModelPruneCommand::models()). Covariant projections break flatMap() template inference, so modelsInModule() returns `Collection<int, non-falsy-string>`.

## Watch out
- Level 7 had 200 errors after this change, mostly argument.type.
- Do not let a typing pass change runtime behaviour, even where PHPStan calls a check redundant. SeedCommand::getModuleRepository() keeps its `instanceof RepositoryInterface` guard and RuntimeException; it reads `$this->laravel['modules']` into an untyped local on purpose, because moduleRepository()'s docblock type would make PHPStan flag the guard as always true. UnUseCommand keeps calling `forgetUsed($module)` although FileRepository ignores the argument: a custom repository may declare the parameter as required. The `@method forgetUsed(Module|null $module = null)` tag exists for that call.
- ModuleManifest::repository() (from #2231) resolves `RepositoryInterface::class`, not `'modules'`, and checks that the result extends FileRepository and scans the same paths. A swapped `'modules'` (Module::swap(), shouldReceive()) is therefore not used there. That is deliberate in #2231; do not "align" it with moduleRepository() without checking the manifest cache tests.
