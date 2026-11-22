---
title: Use the package with AI coding assistants
description: The Laravel Boost guideline and skill that laravel-modules ships, why Boost does not install them, how to add them by hand, and the llms.txt files of this site.
---

The package ships a guideline and a skill in the format of [Laravel Boost](https://github.com/laravel/boost), the Laravel package that gives AI coding assistants guidelines and skills for your application. Boost does not install them today. This page lists what they contain, why, and how to add them to your project yourself. The site also publishes text files for language models.

## What the package ships

The package ships two files, in the repository and in the Composer install under `vendor/nwidart/laravel-modules/.ai/laravel-modules/`.

| File | Kind | Content |
| --- | --- | --- |
| `core.blade.php` | Guideline | Five lines: what the package is, `module:make ModuleName` to scaffold a module in `Modules/`, the `Modules\ModuleName` namespace pattern, and the rule to keep module logic in the module folder, not in `app/`. |
| `1/skill/laravel-modules-development/SKILL.blade.php` | Skill `laravel-modules-development` | Example commands for creating, enabling and disabling modules, for the `module:make-*` generators, for module migrations and for Inertia, plus key conventions, such as the namespace, the `blog::index` view naming and the config key. |

The skill describes itself as the one to use for any task that mentions `module:make`, module commands or a `Modules/` folder. Both files are Blade templates. The skill calls `$assist->artisanCommand()` for each command.

A guideline is loaded when the assistant starts. A skill is loaded when a task needs it. This is how Laravel Boost describes the two.

## Boost does not install these files

`php artisan boost:install` does not install the two files. Laravel Boost reads the guidelines and skills of a third-party package from `resources/boost/guidelines/` and `resources/boost/skills/` inside the package, and only for packages that your application requires directly. `nwidart/laravel-modules` has no `resources/boost/` folder. Boost reads `.ai/<package-name>/` only in its own repository (for the packages it supports itself) and in your project, and the package name there is `nwidart-laravel-modules`, not `laravel-modules`. Nothing in `vendor/nwidart/laravel-modules/.ai/` is read. This holds for Laravel Boost 2.10, the release checked for this page.

After `boost:install`, your assistant therefore has no Laravel Modules guideline and no `laravel-modules-development` skill. This is a gap in the package, and a fix belongs in the package: the files must move to `resources/boost/`.

The skill file has a second problem. In `SKILL.blade.php` the line that closes the front matter (`---`) is indented, and Boost rejects a front matter that has no closing line at the start of a line. Boost then skips the skill and records a parse failure.

## Give the assistant the same context by hand

Install Boost, then add the content to your own project:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

1. **Guideline.** Copy `vendor/nwidart/laravel-modules/.ai/laravel-modules/core.blade.php` to `.ai/guidelines/laravel-modules.blade.php` in your project. The file holds plain Markdown and no Blade directives, so it works as it is. Boost reads every `.md` and `.blade.php` file in `.ai/guidelines/`.
2. **Skill.** Create `.ai/skills/laravel-modules-development/SKILL.md` and write the content of `SKILL.blade.php` into it, with two changes. Put the front matter (`name`, `description`) at the start of the file with a closing `---` that has no indentation. Replace each `{{ $assist->artisanCommand('module:make Blog') }}` with the plain command `php artisan module:make Blog`. Remove the indentation that the source file puts before every line after `metadata:`, because indented lines render as code blocks in Markdown, and drop its `@php` block. Boost reads `SKILL.md` and `SKILL.blade.php` from `.ai/skills/{skill-name}/`.
3. Run `php artisan boost:update`, then check that your assistant lists `laravel-modules-development` among its skills.

See the [Laravel Boost documentation](https://laravel.com/docs/13.x/boost) for the commands of Boost itself.

## Site files for language models

This site publishes plain-text versions of the documentation at the site root:

| File | URL |
| --- | --- |
| `llms.txt` | https://nwidart.github.io/laravel-modules/llms.txt |
| `llms-full.txt` | https://nwidart.github.io/laravel-modules/llms-full.txt |
| `llms-small.txt` | https://nwidart.github.io/laravel-modules/llms-small.txt |

Point an assistant at one of these when it needs the documentation. The `llms-small.txt` file is the compact version. The site build generates all three, so they match the pages you read.

If an assistant's answer disagrees with [Artisan commands](/laravel-modules/reference/commands/) or [Configuration](/laravel-modules/reference/configuration/), trust those pages: they are checked against the code.
