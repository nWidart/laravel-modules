---
title: Community packages
description: Third-party packages that build on laravel-modules, and what each does that the core package does not.
---

These packages work with `nwidart/laravel-modules`. Their authors maintain them. This project does not maintain, test or support them, and a package may not support every version of Laravel Modules. Check its repository for the versions it supports.

The list holds packages that are not archived and that are either named in this project's README or fill a gap of the core package. The "Differs from the core" column comes from each package's own README.

| Package | What it does | Differs from the core |
| --- | --- | --- |
| [`mhmiton/laravel-modules-livewire`](https://github.com/mhmiton/laravel-modules-livewire) | Registers the Livewire components that live in modules, and adds `module:make-livewire` to generate them (single-file and multi-file components). | Laravel Modules has no Livewire integration. The package adds a generator for Livewire components, which `module:make-*` does not have, and registers each module's components so that you use them as `<livewire:core::sfc.post.create />`. |
| [`dcblogdev/laravel-module-generator`](https://github.com/dcblogdev/laravel-module-generator) | Builds a module from a template with `module:build`. | `module:make` writes the fixed set of stubs from `modules.stubs.files`. `module:build` copies a whole folder of files that you choose as a template (the package ships Breeze-based CRUD templates for web and API), replaces placeholders in file names and contents, and asks for the module name and the template. It does not enable the module: you run `module:enable` and `composer dump-autoload` yourself. |

To add a package to this list, see [Contribute and get help](/laravel-modules/ecosystem/contributing-and-support/).
