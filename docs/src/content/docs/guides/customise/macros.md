---
title: Extend the repository and module classes with macros
description: Add methods to the Module facade and to the Module class with macro().
---

To add a method of your own to the module repository or to a module, register a macro. Both classes use Laravel's `Macroable` trait. The package registers no macros.

## Add a method to the repository

The `Module` facade forwards to the repository. Register the macro in the `boot()` method of a service provider of your application. Inside the closure, `$this` is the repository.

```php title="app/Providers/AppServiceProvider.php"
use Nwidart\Modules\Facades\Module;

public function boot(): void
{
    Module::macro('enabledNames', fn () => array_keys($this->allEnabled()));
}
```

Call it through the facade:

```php
Module::enabledNames(); // ['blog', 'shop']
```

## Add a method to every module

Register the macro on `Nwidart\Modules\Module`. Inside the closure, `$this` is the module.

```php title="app/Providers/AppServiceProvider.php"
use Nwidart\Modules\Module as ModuleClass;

public function boot(): void
{
    ModuleClass::macro('routePrefix', fn () => 'api/'.$this->getLowerName());
}
```

Call it on a module:

```php
Module::findOrFail('Blog')->routePrefix(); // 'api/blog'
```

## Check the result

Test the macros in Tinker:

```bash
php artisan tinker --execute="echo json_encode(Module::enabledNames()), PHP_EOL; echo Module::findOrFail('Blog')->routePrefix(), PHP_EOL;"
```

The output lists the names of the enabled modules, then `api/blog`. `Module::hasMacro('enabledNames')` returns `true`.

## Related

- [Repository: macros](/laravel-modules/reference/api/repository/#macros)
- [Module: macros](/laravel-modules/reference/api/module/#macros)
- [Repository API](/laravel-modules/reference/api/repository/) and [Module API](/laravel-modules/reference/api/module/)
