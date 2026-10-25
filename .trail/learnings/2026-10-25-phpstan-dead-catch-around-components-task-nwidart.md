---
title: "phpstan dead catch around components->task differs by PHP target"
author: Nwidart
date: 2026-10-25
branch: chore/phpstan-level-5
cost: { minutes: 14, tokens: 53134 }
id: l-2ab29a7c
scope: repo
status: active
src: agent
touches:
  - phpstan.neon.dist
  - src/Commands/Make/GeneratorCommand.php
---

decided: phpstan.neon.dist pins phpVersion {min: 80300, max: 80599}. Without it PHPStan's single PhpVersion is config.platform.php or the runtime PHP_VERSION_ID (PhpVersionFactoryFactory), not composer require.php, so local 8.4 and CI 8.3 analysed differently. With a range, version-gated rules use min.
why it differed: on target >= 8.4, PropertyFetchHandler adds an implicit throw point for reading a non-private, non-final property of a non-final class (a subclass could add a throwing get hook; PropertyHookThrowPointsResolver). Reading $this->components made every catch in GeneratorCommand::handle() live on 8.4, which hid the catch.neverThrown that 8.3 reports.
why 8.3 reports it: Factory::task is a @method; AnnotationsMethodsClassReflectionExtension takes its throw type from __call's @throws InvalidArgumentException, and a callable passed to a method is not treated as immediately invoked (ArgumentsHandler::callCallbackImmediately, @method params are Maybe).
fix: stubFiles phpstan/stubs/Factory.stub gives __call @throws \Throwable (Task::render rethrows the callback's Throwable).
ruled out: a stub declaring task() with @param-immediately-invoked-callable (stubs cannot add methods that do not exist natively; error stays). @throws FileAlreadyExistException on FileGenerator::generate() alone (closure throw points are not counted).
watch out: stubs are analysed at max level (missingType.iterableValue on array params is non-ignorable). Cold-cache phpstan crashes at the local php.ini memory_limit 128M; pass --memory-limit=1G locally.
