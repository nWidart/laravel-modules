# Laravel Modules

- Laravel Modules is a package for managing large Laravel applications using a modular architecture.
- Each module is an independent unit with its own controllers, models, views, routes, migrations, and configuration.
- Use `module:make ModuleName` to scaffold a new module. Modules live in the `Modules/` directory by default.
- Module namespaces follow the pattern `Modules\ModuleName` (e.g. `Modules\Blog\Http\Controllers\BlogController`).
- Always place module-specific logic inside the module directory, not in the application's `app/` folder.
