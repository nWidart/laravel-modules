---
title: "module:surface stores a module's public surface in module.json as plain, sorted JSON and rewrites the file without Json::save()"
author: Nwidart
date: 2026-11-26
branch: feat/module-surface
id: l-module-surface
scope: repo
status: active
src: agent
touches:
  - src/Commands/Actions/SurfaceCommand.php
  - src/Json.php
  - tests/Commands/Actions/SurfaceCommandTest.php
---

## Decided
- The `surface` key of module.json is read by Mortise in Rust, so its shape is fixed: `{"classes": [], "routes": [], "commands": []}`, always the three keys in this order, each a list of strings, deduplicated and sorted with `sort($list, SORT_STRING)` (byte order, same as Rust `Vec<String>::sort`). An absent key means no accepted surface; empty lists mean the module offers nothing.
- Classes: one leading `\` stripped, PHP identifier rule per segment, deduplicated case-insensitively (first spelling kept). Routes: entries with whitespace or empty are rejected before normalising, then `/` is trimmed from both ends (`/` stays `/`). Commands: Symfony's name rule, exact dedupe.
- module.json is decoded without assoc (`{}` stays `{}`, key order kept), the `surface` property is replaced in place or appended, and the file is encoded with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION`. Zero fraction keeps `1.0` as `1.0`. The trailing newline and CRLF line endings of the original are kept. The write goes through `Filesystem::replace()` (temp file and rename, the file mode passed so it is kept), so a reader never sees half a file.
- A set whose surface equals the stored one (compared by JSON encoding) or a clear without a stored surface writes nothing and reports `changed: false`, even when the file is formatted differently.
- After a write, the command updates the `Json` object the module holds (`set()` / the new `Json::forget()`). The repository's scan and the module share that object, so `find()` and `module()` return the new surface in the same process without `resetModules()`.
- `--stdin` reads from the input's stream when one is set (`StreamableInputInterface::getStream()`), else `STDIN`; tests set a `php://memory` stream on an `ArrayInput` and run it through the console kernel. `readStandardInput()` is protected for subclasses. A leading UTF-8 BOM is stripped (Windows PowerShell pipes add one).
- Exit codes: 0 success, 1 unknown module or module.json unreadable/invalid or write failed, 2 (`Command::INVALID`) usage or validation error. All entries are checked before anything is written.

## Tried and ruled out
- `Json::save()`: it escapes slashes and turns `{}` into `[]` because `Json` decodes to arrays.
- A static helper named `error()` in the command: `Illuminate\Console\Command::error()` is an instance method, and PHP fails at class load ("Cannot make non static method ... static").

## Watch out
- An unreadable or invalid module.json stops the app while it registers (`LaravelModulesServiceProvider::register()` -> `registerModules()` -> `ModuleManifest` -> `FileRepository::getModuleJsonFiles()`), before any command runs, so `module:surface --json` cannot print an errors document then. `skip_broken_modules: true` covers invalid JSON only: `readModuleJson()` catches `InvalidJsonException`, and an unreadable file still throws `ErrorException` ("Permission denied"). Inside `handle()`, a lookup that scans again (after `resetModules()`) can throw either; the command catches any `Throwable` there and exits 1 with the errors document. A module skipped as broken is reported with its reason, not as "does not exist".
- `--command` as an option name does not clash with Symfony's `command` argument: options and arguments are separate in `InputDefinition`, `ArgvInput` (`--command x` and `--command=x`) and `Artisan::call()` (`'--command' => [...]`) both work. A test runs it through `ArgvInput`.
- Patterns end with `$` plus the `D` modifier: without it `$` matches before a trailing newline, so `"blog:publish\n"` would pass.
- Error messages hold user input: they go through `OutputFormatter::escape()` before `components->error()`, else `<fg=red>` in an entry is applied as a style.
- Entries are checked for valid UTF-8 first: the class rule allows bytes `\x80-\xff`, and `json_encode()` throws on invalid UTF-8.
