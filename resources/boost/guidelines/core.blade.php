@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Modules

- nWidart/laravel-modules organises large Laravel applications into self-contained feature bundles under a `Modules/` directory.
- Each module contains its own controllers, models, migrations, routes, views, service providers, and config — like a mini Laravel package inside your app.
- Modules are scaffolded with `{{ $assist->artisanCommand('module:make ModuleName') }}` and live under `Modules/` by default.
- Module namespaces follow `Modules\{StudlyName}` (e.g. `Modules\Blog\Http\Controllers\PostController`).
- Always place module-specific logic inside the module directory — never in the app's `app/` folder.
- Disabled modules have their service providers skipped; their routes, bindings, and migrations will not load.
- Never import classes from another module directly — use events/listeners to avoid hard coupling.
