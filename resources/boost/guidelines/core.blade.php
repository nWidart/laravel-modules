# Laravel Modules

- Laravel Modules is a package for managing large Laravel applications using a modular architecture.
- Each module is an independent unit with its own controllers, models, views, routes, migrations, and configuration.
- Use `module:make ModuleName` to scaffold a new module. Modules live in the `Modules/` directory by default.
- Module namespaces follow the pattern `Modules\ModuleName` (e.g. `Modules\Blog\Http\Controllers\BlogController`).
- Always place module-specific logic inside the module directory, not in the application's `app/` folder.
- With `nwidart/laravel-actuator` installed, a module registers health indicators in the `$healthIndicators` property of its service provider. They appear as `module:{lowercase module}:{key}`. Generate one with `module:make-health-indicator`.
- With `nwidart/laravel-actuator` 1.1.0+ installed, the package also registers the read-only `modules` actuator endpoint (`GET /actuator/modules`, `GET /actuator/modules/{name}`); add `modules` to `actuator.exposure.include` to expose it.
