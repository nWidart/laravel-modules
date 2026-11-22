---
title: Events
description: The events a module fires when it is created, used, enabled, disabled and deleted, with name, payload and trigger.
---

A module dispatches string events through Laravel's event dispatcher. The constants are in `Nwidart\Modules\Constants\ModuleEvent`.

## Event names

The name has the form `modules.{lowername}.{event}`. `{lowername}` is the lower-case module name. For the module `Blog`, enabling the module fires `modules.blog.enabled`.

## Events

| Event | Constant | Fired by | Payload | Status |
| --- | --- | --- | --- | --- |
| `creating` | `ModuleEvent::CREATING` | `module:make`, after the checks and the rendering of the stubs, before any folder or file is written. With `--force`, after the existing module is deleted. | none | Fired |
| `created` | `ModuleEvent::CREATED` | `module:make`, after the module status is set. | the `Module` | Fired |
| `enabling` | `ModuleEvent::ENABLING` | `Module::enable()`, before the activator changes the status. This covers `module:enable` and `Repository::enable()`. | the `Module` | Fired |
| `enabled` | `ModuleEvent::ENABLED` | `Module::enable()`, after the activator changes the status. | the `Module` | Fired |
| `disabling` | `ModuleEvent::DISABLING` | `Module::disable()`, before the activator changes the status. This covers `module:disable` and `Repository::disable()`. | the `Module` | Fired |
| `disabled` | `ModuleEvent::DISABLED` | `Module::disable()`, after the activator changes the status. | the `Module` | Fired |
| `deleting` | `ModuleEvent::DELETING` | `Module::delete()`, before the status and the folder are removed. This covers `module:delete`, `Repository::delete()` and `module:make --force` on an existing module. | the `Module` | Fired |
| `deleted` | `ModuleEvent::DELETED` | `Module::delete()`, after the folder is removed. | the `Module` | Fired |
| `used` | `ModuleEvent::USED` | `Repository::setUsed()`, which `module:use` calls. | the `Module` | Fired |
| `unused` | `ModuleEvent::UNUSED` | `Repository::forgetUsed()` (`module:unuse`), after deleting the stored file. Skipped if no module is in use or the stored module is missing. | the `Module` | Fired |
| `register` | `ModuleEvent::REGISTER` | `Module::register()`. | the `Module` | Deprecated. The package never calls `Module::register()`. Removed in version 14. |
| `boot` | `ModuleEvent::BOOT` | `Module::boot()`. | the `Module` | Deprecated. The package never calls `Module::boot()`. Removed in version 14. |

`Module::setActive()` and the status of a new module (set by `module:make`, or disabled with `--disabled`) fire neither `enabling` nor `enabled`.

## Payload

The dispatcher calls a listener with the payload as its arguments. A listener for an event with the payload `the Module` receives the `Nwidart\Modules\Module` instance as its first argument. A listener for `creating` receives no argument.

For a wildcard listener, the dispatcher passes the event name and an array that holds the payload.

## Listener example

```php
use Illuminate\Support\Facades\Event;
use Nwidart\Modules\Module;

// In the boot() method of a service provider of the application.
Event::listen('modules.blog.enabled', function (Module $module) {
    logger()->info("Module {$module->getName()} enabled.");
});

Event::listen('modules.*.disabled', function (string $event, array $payload) {
    [$module] = $payload;

    logger()->info("Module {$module->getName()} disabled.");
});
```

A module's own listeners register at boot only while the module is enabled. When a process enables a module, that module was disabled at boot, so its `enabled` listener does not run in that process. When a process disables a module, the module was enabled at boot, so its `disabling` and `disabled` listeners run.
