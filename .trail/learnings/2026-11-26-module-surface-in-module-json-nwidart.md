---
title: "module:surface stores a module's public surface in module.json as plain, sorted JSON by editing only that member's text, written in place"
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
  - src/Support/JsonObjectEditor.php
  - src/Traits/WritesJsonOutput.php
  - tests/Commands/Actions/SurfaceCommandTest.php
---

## Decided
- The `surface` key of module.json is read by Mortise in Rust, so its shape is fixed: `{"classes": [], "routes": [], "commands": []}`, always the three keys in this order, each a list of strings, deduplicated and sorted with `sort($list, SORT_STRING)` (byte order, same as Rust `Vec<String>::sort`). An absent key means no accepted surface; empty lists mean the module offers nothing.
- Classes: one leading `\` stripped, PHP identifier rule per segment, deduplicated case-insensitively (first spelling kept). Routes: entries with whitespace or empty are rejected before normalising, then `/` is trimmed from both ends (`/` stays `/`). Commands: Symfony's name rule, exact dedupe.
- module.json is edited as text, not re-encoded: `json_decode()` (without assoc) only validates the file and reads the stored surface. `Support\JsonObjectEditor` scans the top-level object (strings with escapes, nesting depth) for the `"surface"` member and replaces its value, appends it after the last member (before the whitespace that closes the object), or removes it with its comma. Every other byte stays: integers past `PHP_INT_MAX`, floats with 17+ digits, `1.0`, `\u00e9` and `\/` escapes, `{}`, the indent. The value is encoded with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`, re-indented to depth 1 with the file's indent unit (the whitespace before the first member; 4 spaces for `{}`), with CRLF in a CRLF file. A compact one-line file gets a compact member. With duplicate keys the last one (the one `json_decode()` reads) is replaced, and clear removes all.
- Just before writing, the file is read again; when its bytes differ from the first read, nothing is written and the command exits 1 ("module.json changed while module:surface ran; run it again."). The protected `beforeWrite()` hook runs between the two reads, for tests.
- The file is written in place with `Filesystem::put($path, $json, true)` (`LOCK_EX`), like the package's other module.json writes. It keeps owner, group, mode, ACLs, hard links and symlinks.
- `Traits\WritesJsonOutput::writeJson()` adds `JSON_INVALID_UTF8_SUBSTITUTE`: a module argument with invalid UTF-8 still gives a parseable document, with U+FFFD in its place.
- Show checks each stored entry with the `--set` rules and requires its normalised form to equal the stored string (a leading `\` or a route with a trailing `/` is invalid): invalid exits 1 with errors. Valid entries are printed as stored (spelling, order, duplicates).
- A set whose surface equals the stored one (compared by JSON encoding) or a clear without a stored surface writes nothing and reports `changed: false`, even when the file is formatted differently.
- After a write, the command updates the `Json` object the module holds (`set()` / the new `Json::forget()`). The repository's scan and the module share that object, so `find()` and `module()` return the new surface in the same process without `resetModules()`.
- `--stdin` reads from the input's stream when one is set (`StreamableInputInterface::getStream()`), else `STDIN`; tests set a `php://memory` stream on an `ArrayInput` and run it through the console kernel. `readStandardInput()` is protected for subclasses. A leading UTF-8 BOM is stripped (Windows PowerShell pipes add one).
- Exit codes: 0 success, 1 unknown module or module.json unreadable/invalid or write failed, 2 (`Command::INVALID`) usage or validation error. All entries are checked before anything is written.

## Tried and ruled out
- `Json::save()`: it escapes slashes and turns `{}` into `[]` because `Json` decodes to arrays.
- Decoding the whole file and encoding it again (first version, PR review): `json_decode()` rounds `9223372036854775809` and long floats to doubles, so the rewrite changes them, and it drops escapes and the indent.
- `Filesystem::replace()` (temp file and rename): the new file gets the process's owner and group, loses ACLs and breaks hard links. The trade-off of writing in place: a reader can see a partly written file during the write.
- A static helper named `error()` in the command: `Illuminate\Console\Command::error()` is an instance method, and PHP fails at class load ("Cannot make non static method ... static").

## Watch out
- An unreadable or invalid module.json stops the app while it registers (`LaravelModulesServiceProvider::register()` -> `registerModules()` -> `ModuleManifest` -> `FileRepository::getModuleJsonFiles()`), before any command runs, so `module:surface --json` cannot print an errors document then. `skip_broken_modules: true` covers invalid JSON only: `readModuleJson()` catches `InvalidJsonException`, and an unreadable file still throws `ErrorException` ("Permission denied"). Inside `handle()`, a lookup that scans again (after `resetModules()`) can throw either; the command catches any `Throwable` there and exits 1 with the errors document. A module skipped as broken is reported with its reason, not as "does not exist".
- `--command` as an option name does not clash with Symfony's `command` argument: options and arguments are separate in `InputDefinition`, `ArgvInput` (`--command x` and `--command=x`) and `Artisan::call()` (`'--command' => [...]`) both work. A test runs it through `ArgvInput`.
- Patterns end with `$` plus the `D` modifier: without it `$` matches before a trailing newline, so `"blog:publish\n"` would pass.
- Error messages hold user input: they go through `OutputFormatter::escape()` before `components->error()`, else `<fg=red>` in an entry is applied as a style.
- Entries are checked for valid UTF-8 first: the class rule allows bytes `\x80-\xff`, and `json_encode()` throws on invalid UTF-8.
