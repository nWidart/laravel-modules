---
title: "--json mode: Traits\\WritesJsonOutput swaps $this->output and $this->components in handle(); Artisan::call() has no stderr"
author: Nwidart
date: 2026-11-23
branch: feat/generators-json
id: l-json-output-mode
scope: repo
status: active
src: agent
touches:
  - src/Traits/WritesJsonOutput.php
  - src/Commands/Actions/ListGeneratorsCommand.php
  - tests/Traits/WritesJsonOutputTest.php
---

## Decided
- A command with `--json` uses `Traits\WritesJsonOutput`: `enterJsonMode()` first in `handle()`, then `writeJson($document)` once. The trait does not declare the option; each command does.
- The real output sits under Laravel's `OutputStyle`: `$this->output->getOutput()`. The CLI passes a `ConsoleOutput`, so messages go to its `getErrorOutput()`. `Artisan::call()` and `$this->artisan()` (with `withoutMockingConsoleOutput()`, as in `BaseTestCase`) pass a `BufferedOutput`, which has no error stream, so messages go to a `NullOutput` and `Artisan::output()` holds only the JSON line.
- `$this->components` must be rebuilt on the new `OutputStyle`; the Factory keeps its own output reference. `Prompt::setOutput()` is reset too.
- The JSON is written with `OUTPUT_RAW | VERBOSITY_QUIET`: raw keeps `<info>` literal and ANSI-free under `--ansi`, quiet keeps it printed with `-q`.

## Tried and ruled out
- Switching in `initialize()` from the trait: `BaseCommand` defines `initialize()`, and a class that defines its own silently wins over the trait, so the switch would depend on the class tree. An explicit call in `handle()` works for `Command`, `BaseCommand` and `GeneratorCommand` alike.

## Watch out
- `module:generators` reads `getNativeDefinition()`: `getDefinition()` includes the application options (help, env, ansi, ...) once the command has been merged.
- On `module:make-model`, `-f` is `--factory`, so `--force` has no shortcut there; `--fillable` is `VALUE_OPTIONAL` (value_required false).
- Running `vendor/bin/testbench <command>` in a checkout replaces `vendor/orchestra/testbench-core/laravel/vendor` with a symlink to the checkout's `vendor` (testbench `CreateVendorSymlink`). After that, the `composer dump-autoload` that `module:make` runs in `base_path()` during tests rewrites the checkout's autoloader from the skeleton's composer.json, and the suite dies with `Class "Nwidart\Modules\Tests\BaseTestCase" not found`. Fix: `rm vendor/orchestra/testbench-core/laravel/vendor && composer dump-autoload`. Try commands in a QA app instead.
- Tests count 43 package generators (`module:make` + 42 `GeneratorCommand` subclasses) and require every one in `ListGeneratorsCommand::GROUPS`; a new generator must be added to the map.
