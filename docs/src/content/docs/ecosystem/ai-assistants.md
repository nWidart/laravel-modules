---
title: Use the package with AI coding assistants
description: The Laravel Boost guideline and skill that laravel-modules ships, how to turn them on, and the llms.txt files of this site.
---

The package ships a guideline and a skill in the format of [Laravel Boost](https://github.com/laravel/boost), the Laravel package that gives AI coding assistants guidelines and skills for your application. Boost discovers them, and you opt in to install them. This page lists what they contain and how to turn them on. The site also publishes text files for language models.

## What the package ships

The package ships two files in `resources/boost/`, in the repository and in the Composer install under `vendor/nwidart/laravel-modules/resources/boost/`.

| File | Kind | Content |
| --- | --- | --- |
| `resources/boost/guidelines/core.blade.php` | Guideline | Five lines: what the package is, `module:make ModuleName` to scaffold a module in `Modules/`, the `Modules\ModuleName` namespace pattern, and the rule to keep module logic in the module folder, not in `app/`. |
| `resources/boost/skills/laravel-modules-development/SKILL.blade.php` | Skill `laravel-modules-development` | Example commands for creating, enabling and disabling modules, for the `module:make-*` generators, for module migrations and for Inertia, plus key conventions, such as the namespace, the `blog::index` view naming and the config key. |

The skill describes itself as the one to use for any task that mentions `module:make`, module commands or a `Modules/` folder. Both files are Blade templates. The skill calls `$assist->artisanCommand()` for each command.

A guideline is loaded when the assistant starts. A skill is loaded when a task needs it. This is how Laravel Boost describes the two.

## Turn the guideline and skill on

Boost reads `resources/boost/guidelines/` and `resources/boost/skills/` in each package that your application requires. With Laravel Boost 2.10.4, the release checked for this page, it lists the package as `nwidart/laravel-modules (guidelines, skills)`. Boost does not install them until you choose them.

Install Boost, then pick the package in the prompt:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Select `nwidart/laravel-modules` in the package prompt of `boost:install`. `boost:update` offers the same choice later.

To set the choice without the prompt, list the package in `boost.json` in the project root:

```json title="boost.json"
{
    "packages": ["nwidart/laravel-modules"]
}
```

Run `php artisan boost:update` after you edit the file, then check that your assistant lists `laravel-modules-development` among its skills. See the [Laravel Boost documentation](https://laravel.com/docs/13.x/boost) for the commands of Boost itself.

## Site files for language models

This site publishes plain-text versions of the documentation at the site root:

| File | URL |
| --- | --- |
| `llms.txt` | https://nwidart.github.io/laravel-modules/llms.txt |
| `llms-full.txt` | https://nwidart.github.io/laravel-modules/llms-full.txt |
| `llms-small.txt` | https://nwidart.github.io/laravel-modules/llms-small.txt |

Point an assistant at one of these when it needs the documentation. The `llms-small.txt` file is the compact version. The site build generates all three, so they match the pages you read.

If an assistant's answer disagrees with [Artisan commands](/laravel-modules/reference/commands/) or [Configuration](/laravel-modules/reference/configuration/), trust those pages: they are checked against the code.
