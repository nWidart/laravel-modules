---
title: "A file deleted and written again within one second can look unchanged on Linux: backdate files before the run when a test must see the rewrite"
author: Nwidart
date: 2026-11-23
branch: feat/generator-json-result
id: l-rewritten-files-look-unchanged-on-linux
scope: repo
status: active
src: agent
touches:
  - tests/Commands/Make/Concerns/InspectsGeneratorRuns.php
  - tests/Commands/Make/GeneratorJsonResultTest.php
---

## Decided
- `InspectsGeneratorRuns::listing()` marks a file as changed by its contents hash, mtime (whole seconds) and inode. `module:make --force` deletes the module and writes the same files again in the same second, and ext4 on the CI runners gives each new file the inode the deleted one had. All three matched, `changedFiles()` returned nothing, and `test_module_make_force_json_deletes_the_module_and_creates_its_files_again` failed on Ubuntu (PHP 8.3 to 8.5) while it passed on macOS, where APFS does not reuse inodes that way.
- The test calls `backdate()` on the module before it takes `$before`: every file gets the mtime 2000-01-01, so a file the run writes again gets a new mtime on any filesystem. The test then checks that every file in the module after the run is created or changed, and that the JSON paths equal every file in the module (`files($after)`).

## Watch out
- Use `backdate()` before any assertion that a file *was* rewritten. Assertions that a file was *not* changed (dry-run tests, the `keep` test) cannot fail spuriously this way; they can only miss a rewrite with the same contents.
- To reproduce the CI failure on macOS, drop `'#'.$file->getInode()` from `listing()` and run the old test: it fails every time.
