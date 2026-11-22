---
title: Control the load order of modules
description: Set priority in module.json so a module registers its providers before or after another, and list the resulting order.
---

To make `Shop` register before `Blog`, give `Shop` a lower `priority` than `Blog`. The package registers the providers of the enabled modules in ascending `priority` order.

## Set the priority

Edit the `priority` field in the `module.json` of each module. A module without the field has priority `0`.

```json title="Modules/Shop/module.json"
{
    "name": "Shop",
    "alias": "shop",
    "description": "",
    "keywords": [],
    "priority": 1,
    "providers": [
        "Modules\\Shop\\Providers\\ShopServiceProvider"
    ],
    "files": []
}
```

```json title="Modules/Blog/module.json"
{
    "name": "Blog",
    "alias": "blog",
    "description": "",
    "keywords": [],
    "priority": 10,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "files": []
}
```

Modules with the same priority load in the order the scan finds them.

## Check the result

List the enabled modules by priority.

```bash
php artisan module:list --only=priority
```

The first line is the module that loads first:

```text
[Enabled] Shop ........ Modules/Shop [1]
[Enabled] Blog ........ Modules/Blog [10]
```

To list the highest priority first, add `--direction=desc`. The command lists enabled modules only: a disabled module does not load.

## Related

- [`priority` in module.json](/laravel-modules/reference/module-json/)
- [`module:list`](/laravel-modules/reference/commands/module-management/#modulelist)
- [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/)
