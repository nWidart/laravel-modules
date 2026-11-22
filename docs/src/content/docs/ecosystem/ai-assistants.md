---
title: Use the package with AI coding assistants
description: The Laravel Boost guideline and skill that laravel-modules ships, how to turn them on, and the llms.txt files of this site.
---

The package ships a guideline and a skill for [Laravel Boost](https://github.com/laravel/boost). Boost discovers package guidelines and skills automatically, and you opt in to install them. This site also publishes plain-text `llms.txt` files for language models.

## What the package ships

Two Blade templates ship under `resources/boost/` (installed at `vendor/nwidart/laravel-modules/resources/boost/`):

| File | Kind | Content |
| --- | --- | --- |
| `resources/boost/guidelines/core.blade.php` | Guideline | Seven bullets: modular layout, `module:make`, `Modules\ModuleName` namespaces, keeping logic out of `app/`, Actuator `$healthIndicators` (`module:make-health-indicator`), and the `modules` actuator endpoint (`GET /actuator/modules`). |
| `resources/boost/skills/laravel-modules-development/SKILL.blade.php` | Skill (`laravel-modules-development`) | Commands and conventions for modules, generators (`module:make-config`, `module:make-job-middleware`, `--attributes`, `--json-api`), event discovery, health indicators, `GET /actuator/modules`, migrations, and Inertia v3. |

The skill activates for tasks mentioning `module:make`, module commands, or a `Modules/` directory. It renders commands through `$assist->artisanCommand()`.

In Laravel Boost, guidelines load at session start while skills load on demand.

## Turn the guideline and skill on

Boost reads `resources/boost/guidelines/` and `resources/boost/skills/` from installed Composer packages. In Laravel Boost 2.10.4, it lists `nwidart/laravel-modules (guidelines, skills)` and waits for you to opt in.

Install Boost and select the package when prompted:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Pick `nwidart/laravel-modules` in the prompt (`boost:update` offers the same prompt later).

To configure it without a prompt, add the package to `boost.json` in your project root:

```json title="boost.json"
{
    "packages": ["nwidart/laravel-modules"]
}
```

Run `php artisan boost:update`, then verify that your assistant lists `laravel-modules-development`. See the [Laravel Boost documentation](https://laravel.com/docs/13.x/boost) for Boost commands.

## Site files for language models

This site publishes plain-text versions of the documentation at the site root:

| File | URL |
| --- | --- |
| `llms.txt` | https://nwidart.github.io/laravel-modules/llms.txt |
| `llms-full.txt` | https://nwidart.github.io/laravel-modules/llms-full.txt |
| `llms-small.txt` | https://nwidart.github.io/laravel-modules/llms-small.txt |

Point your assistant at one of these files when it needs full documentation context. `llms-small.txt` is the compact version. The site build generates all three from these pages.

When an assistant disagrees with [Artisan commands](/laravel-modules/reference/commands/) or [Configuration](/laravel-modules/reference/configuration/), trust those reference pages: they are verified against the source code.
