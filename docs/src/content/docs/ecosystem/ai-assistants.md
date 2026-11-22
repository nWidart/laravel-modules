---
title: Use the package with AI coding assistants
description: The Laravel Boost guideline and skill that laravel-modules ships, what they contain, and the llms.txt files of this site.
---

The package ships two files for [Laravel Boost](https://github.com/laravel/boost), the Laravel package that gives AI coding assistants guidelines and skills for your application. This page lists what they contain and where to find them. The site also publishes text files for language models.

## What the package ships

The files are in the repository under `.ai/laravel-modules/`.

| File | Kind | Content |
| --- | --- | --- |
| `.ai/laravel-modules/core.blade.php` | Guideline | Five lines: what the package is, `module:make ModuleName` to scaffold a module in `Modules/`, the `Modules\ModuleName` namespace pattern, and the rule to keep module logic in the module folder, not in `app/`. |
| `.ai/laravel-modules/1/skill/laravel-modules-development/SKILL.blade.php` | Skill `laravel-modules-development` | Example commands for creating, enabling and disabling modules, for the `module:make-*` generators, for module migrations and for Inertia, plus key conventions, such as the namespace, the `blog::index` view naming and the config key. |

The skill describes itself as the one to use for any task that mentions `module:make`, module commands or a `Modules/` folder. It sends the assistant to Boost's `search-docs` tool for details. Both files are Blade templates. The skill calls `$assist->artisanCommand()` for each command.

A guideline is loaded when the assistant starts. A skill is loaded when a task needs it. This is how Laravel Boost describes the two.

## Install Boost

```bash
composer require laravel/boost --dev
php artisan boost:install
```

`boost:install` writes the guideline and skill files for the assistants you select. Run `php artisan boost:update` after you update packages. See the [Laravel Boost documentation](https://laravel.com/docs/13.x/boost).

## How Boost finds the files

The Laravel Boost documentation describes how a third-party package ships guidelines and skills: `resources/boost/guidelines/core.blade.php` for a guideline, and `resources/boost/skills/{skill-name}/SKILL.md` for a skill, inside the package.

Laravel Modules ships its files under `.ai/laravel-modules/` instead, and the repository has no `resources/boost/` folder. The Boost documentation does not describe a `.ai/<package>/` folder inside a third-party package. The documentation does not confirm that `boost:install` installs these two files from `vendor/nwidart/laravel-modules`. Check the result on your project: after `boost:install`, look for `laravel-modules-development` among the installed skills, and for the Laravel Modules text in the guideline file of your assistant.

If the files are not picked up, you can give the assistant the same context yourself. Boost reads your own `.ai/guidelines/` folder for guidelines, and `.ai/skills/{skill-name}/SKILL.md` for skills. Copy the content of the two files there.

## Site files for language models

This site publishes plain-text versions of the documentation at the site root:

| File | URL |
| --- | --- |
| `llms.txt` | https://nwidart.github.io/laravel-modules/llms.txt |
| `llms-full.txt` | https://nwidart.github.io/laravel-modules/llms-full.txt |
| `llms-small.txt` | https://nwidart.github.io/laravel-modules/llms-small.txt |

Point an assistant at one of these when it needs the documentation. The `llms-small.txt` file is the compact version. The site build generates all three, so they match the pages you read.

If an assistant's answer disagrees with [Artisan commands](/laravel-modules/reference/commands/) or [Configuration](/laravel-modules/reference/configuration/), trust those pages: they are checked against the code.
