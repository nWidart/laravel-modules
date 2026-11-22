---
title: Stubs and placeholders
description: The stub files the package ships, the $TOKEN$ placeholder syntax, the built-in replacement keys, conditional sections, stub lookup and replacement classes.
---

A stub is a template file that a generator command renders into a module file. This page describes the stubs the package ships and the rules that render them.

## Stub files

The stubs are in `src/Commands/stubs/` of the package. Their names end in `.stub`. A stub key is the path of the stub without `.stub`, for example `routes/web`.

The Tokens column lists the `$TOKEN$` placeholders found in the stub file.

| Stub | Used by | Tokens |
| --- | --- | --- |
| `action.stub` | `module:make-action` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `action-invoke.stub` | `module:make-action` `--invokable` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `assets/js/app.stub` | `module:make` (`stubs.files`) | none |
| `assets/sass/app.stub` | `module:make` (`stubs.files`) | none |
| `cast.stub` | `module:make-cast` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `channel.stub` | `module:make-channel` | `$CLASS$` `$NAMESPACE$` |
| `class.stub` | `module:make-class` | `$CLASS$` `$NAMESPACE$` |
| `class-invoke.stub` | `module:make-class` `--invokable` | `$CLASS$` `$NAMESPACE$` |
| `command.stub` | `module:make-command` | `$CLASS$` `$COMMAND_NAME$` `$NAMESPACE$` |
| `component-class.stub` | `module:make-component` | `$CLASS$` `$COMPONENT_NAME$` `$LOWER_NAME$` `$NAMESPACE$` |
| `component-view.stub` | `module:make-component-view` | `$QUOTE$` |
| `composer.stub` | `module:make` (`composer.json`, `stubs.files`) | `$APP_FOLDER_NAME$` `$AUTHOR_EMAIL$` `$AUTHOR_NAME$` `$LOWER_NAME$` `$MODULE_NAMESPACE$` `$STUDLY_NAME$` `$VENDOR$` |
| `config.stub` | `module:make-config` | none |
| `controller.stub` | `module:make-controller` | `$CLASS$` `$CLASS_NAMESPACE$` `$LOWER_NAME$` |
| `controller-api.stub` | `module:make-controller` `--api` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `controller-inertia.stub` | `module:make-controller` `--inertia` | `$CLASS$` `$CLASS_NAMESPACE$` `$STUDLY_NAME$` |
| `controller-plain.stub` | `module:make-controller` `--plain` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `controller.invokable.stub` | `module:make-controller` `--invokable` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `enum.stub` | `module:make-enum` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `event.stub` | `module:make-event` | `$CLASS$` `$NAMESPACE$` |
| `event-provider.stub` | `module:make-event-provider` | `$CLASS$` `$NAMESPACE$` |
| `exception.stub` | `module:make-exception` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `exception-render.stub` | `module:make-exception` `--render` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `exception-render-report.stub` | `module:make-exception` `--render` `--report` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `exception-report.stub` | `module:make-exception` `--report` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `factory.stub` | `module:make-factory` | `$MODEL_NAMESPACE$` `$NAME$` `$NAMESPACE$` |
| `helper.stub` | `module:make-helper` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `helper-invoke.stub` | `module:make-helper` `--invokable` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `inertia/app-react.stub` | `module:publish-inertia` `--react` | none |
| `inertia/app-svelte.stub` | `module:publish-inertia` `--svelte` | none |
| `inertia/app-vue.stub` | `module:publish-inertia` `--vue` | none |
| `inertia/component-react.stub` | `module:make-inertia-component` `--react` | `$COMPONENT_NAME$` |
| `inertia/component-svelte.stub` | `module:make-inertia-component` `--svelte` | `$COMPONENT_NAME$` |
| `inertia/component-vue.stub` | `module:make-inertia-component` `--vue` | `$COMPONENT_NAME$` |
| `inertia/page-react.stub` | `module:make-inertia-page` `--react` | `$PAGE_NAME$` `$STUDLY_NAME$` |
| `inertia/page-svelte.stub` | `module:make-inertia-page` `--svelte` | `$PAGE_NAME$` `$STUDLY_NAME$` |
| `inertia/page-vue.stub` | `module:make-inertia-page` `--vue` | `$PAGE_NAME$` `$STUDLY_NAME$` |
| `interface.stub` | `module:make-interface` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `job.stub` | `module:make-job` `--sync` | `$CLASS$` `$NAMESPACE$` |
| `job-middleware.stub` | `module:make-job-middleware` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `job-queued.stub` | `module:make-job` | `$CLASS$` `$NAMESPACE$` |
| `json.stub` | `module:make` (`module.json`) | `$LOWER_NAME$` `$MODULE_NAMESPACE$` `$PROVIDER_NAMESPACE$` `$STUDLY_NAME$` |
| `listener.stub` | `module:make-listener` `--event` | `$CLASS$` `$EVENTNAME$` `$NAMESPACE$` `$SHORTEVENTNAME$` |
| `listener-duck.stub` | `module:make-listener` | `$CLASS$` `$NAMESPACE$` |
| `listener-queued.stub` | `module:make-listener` `--event` `--queued` | `$CLASS$` `$EVENTNAME$` `$NAMESPACE$` `$SHORTEVENTNAME$` |
| `listener-queued-duck.stub` | `module:make-listener` `--queued` | `$CLASS$` `$NAMESPACE$` |
| `mail.stub` | `module:make-mail` | `$CLASS$` `$NAMESPACE$` |
| `middleware.stub` | `module:make-middleware` | `$CLASS$` `$NAMESPACE$` |
| `middleware/handle-inertia-requests.stub` | `module:make-middleware` `--inertia` | `$CLASS$` `$NAMESPACE$` |
| `migration/add.stub` | `module:make-migration` (`add_*`, `update_*`, `append_*`, `insert_*`) | `$FIELDS_DOWN$` `$FIELDS_UP$` `$TABLE$` |
| `migration/create.stub` | `module:make-migration` (`create_*`, `make_*`) | `$FIELDS$` `$TABLE$` |
| `migration/delete.stub` | `module:make-migration` (`delete_*`, `remove_*`) | `$FIELDS_DOWN$` `$FIELDS_UP$` `$TABLE$` |
| `migration/drop.stub` | `module:make-migration` (`destroy_*`, `drop_*`) | `$FIELDS$` `$TABLE$` |
| `migration/plain.stub` | `module:make-migration` (any other name) | none |
| `model.stub` | `module:make-model` | `$CLASS$` `$FACTORY$` `$FACTORY_CLASS$` `$FILLABLE$` `$NAMESPACE$` |
| `notification.stub` | `module:make-notification` | `$CLASS$` `$NAMESPACE$` |
| `observer.stub` | `module:make-observer` | `$MODEL_NAMESPACE$` `$NAME$` `$NAMESPACE$` `$NAME_VARIABLE$` |
| `package.stub` | `module:make` (`stubs.files`) | none |
| `policy.plain.stub` | `module:make-policy` | `$CLASS$` `$NAMESPACE$` |
| `provider.stub` | `module:make-provider` | `$CLASS$` `$NAMESPACE$` |
| `replacement.stub` | `module:make-replacement` | `$CLASS$` `$NAMESPACE$` |
| `repository.stub` | `module:make-repository` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `repository-invoke.stub` | `module:make-repository` `--invokable` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `request.stub` | `module:make-request` | `$CLASS$` `$NAMESPACE$` |
| `resource.stub` | `module:make-resource` | `$CLASS$` `$NAMESPACE$` |
| `resource-collection.stub` | `module:make-resource` `--collection` | `$CLASS$` `$NAMESPACE$` |
| `resource-json-api.stub` | `module:make-resource` `--json-api` | `$CLASS$` `$NAMESPACE$` |
| `route-provider.stub` | `module:route-provider` | `$API_ROUTES_PATH$` `$CLASS$` `$MODULE$` `$NAMESPACE$` `$WEB_ROUTES_PATH$` |
| `routes/api.stub` | `module:make` (`stubs.files`) | `$CONTROLLER_NAMESPACE$` `$LOWER_NAME$` `$MODULE_NAMESPACE$` `$PLURAL_LOWER_NAME$` `$STUDLY_NAME$` |
| `routes/web.stub` | `module:make` (`stubs.files`) | `$CONTROLLER_NAMESPACE$` `$LOWER_NAME$` `$MODULE_NAMESPACE$` `$PLURAL_LOWER_NAME$` `$STUDLY_NAME$` |
| `rule.stub` | `module:make-rule` | `$CLASS$` `$NAMESPACE$` |
| `rule.implicit.stub` | `module:make-rule` `--implicit` | `$CLASS$` `$NAMESPACE$` |
| `scaffold/config.stub` | `module:make` (`stubs.files`) | `$STUDLY_NAME$` |
| `scaffold/provider.stub` | `module:make-provider` `--master` | `$CLASS$` `$LOWER_NAME$` `$MODULE$` `$NAMESPACE$` `$PROVIDERS$` |
| `scope.stub` | `module:make-scope` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `seeder.stub` | `module:make-seed` | `$NAME$` `$NAMESPACE$` |
| `service.stub` | `module:make-service` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `service-invoke.stub` | `module:make-service` `--invokable` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `tests/feature.stub` | `module:make-test` `--feature` | `$CLASS$` `$NAMESPACE$` |
| `tests/unit.stub` | `module:make-test` | `$CLASS$` `$NAMESPACE$` |
| `trait.stub` | `module:make-trait` | `$CLASS$` `$CLASS_NAMESPACE$` |
| `view.stub` | `module:make-view` | `$QUOTE$` |
| `views/index.stub` | `module:make` (`stubs.files`) | `$LOWER_NAME$` |
| `views/master.stub` | `module:make` (`stubs.files`) | `$LOWER_NAME$` `$STUDLY_NAME$` |
| `vite.stub` | `module:make` (`stubs.files`) | `$LOWER_NAME$` |

A `-duck` stub (`listener-duck.stub`, `listener-queued-duck.stub`) generates a listener whose `handle()` method takes an untyped `$event`, instead of the type of an `--event` class.

## Placeholder syntax

A placeholder is an upper-case name wrapped in dollar signs: `$LOWER_NAME$`. The name holds letters and underscores.

- Rendering replaces every `$NAME$` for which the generator passes a value, in one pass.
- The generator uppercases each key it passes. The key `class` replaces `$CLASS$`.
- When a key is passed twice, the first value wins.
- A placeholder without a value stays in the output as written.
- `module:make` prints a warning for each stub that still holds a `$UPPER_CASE$` token after rendering. The warning names the stub key and says which `stubs.replacements` list to extend.
- Replaced values are inserted as given. `module:make` JSON-escapes the built-in values in `json` and in every stub whose target file ends in `.json`. `module:make-command` escapes `\` and `'` in `$COMMAND_NAME$`, because `command.stub` puts it in a single-quoted PHP string.

## Built-in replacement keys

The `module:make` stubs (`stubs.files` and `json`) take the keys listed in `stubs.replacements` under their stub key. These are the built-in names:

| Key | Value |
| --- | --- |
| `LOWER_NAME` | Module name in lower case. |
| `STUDLY_NAME` | Module name in studly case. |
| `KEBAB_NAME` | Module name in kebab case. |
| `PLURAL_LOWER_NAME` | Module name in lower case, plural. |
| `PLURAL_STUDLY_NAME` | Module name in studly case, plural. |
| `PLURAL_UPPER_NAME` | Module name in upper case, plural. |
| `VENDOR` | The `--author-vendor` option, else `composer.vendor`. |
| `AUTHOR_NAME` | The `--author-name` option, else `composer.author.name`. |
| `AUTHOR_EMAIL` | The `--author-email` option, else `composer.author.email`. |
| `MODULE_NAMESPACE` | The `namespace` config value. |
| `CONTROLLER_NAMESPACE` | `paths.generator.controller.namespace`, else the `controller` path without `paths.app_folder`, as a namespace. |
| `PROVIDER_NAMESPACE` | Namespace of the `provider` generator entry. |
| `APP_FOLDER_NAME` | `paths.app_folder`. |

A name in a `stubs.replacements` list that is neither built-in nor a custom replacement class prints `Unknown replacement key [<name>] for stub [<stub key>]. It was replaced with an empty string.` A stub key without an entry in `stubs.replacements` gets no replacement. The default lists are in [Configuration](/laravel-modules/reference/configuration/#stubs).

## Tokens of generator commands

The `module:make-*` commands pass their own keys. These are not configurable. The set of each stub is in the Tokens column above.

| Token | Value |
| --- | --- |
| `CLASS` | Class name without namespace. |
| `NAMESPACE`, `CLASS_NAMESPACE` | Namespace of the generated class. |
| `LOWER_NAME`, `STUDLY_NAME` | Module name in lower case and in studly case. |
| `NAME` | Model name (factory, observer stubs) or seeder class name (seeder stub). |
| `MODEL_NAMESPACE` | Namespace of the model folder of the module. |
| `NAME_VARIABLE` | `$` followed by the lower-case name (observer stub). |
| `FILLABLE` | JSON array of the `--fillable` values (model stub). |
| `FACTORY`, `FACTORY_CLASS` | Short name and fully qualified name of the factory. Empty without `--factory`. |
| `COMMAND_NAME` | The `--command` value, default `command:name`. |
| `COMPONENT_NAME` | `components.<name in lower case>` (component-class stub); studly-case component name (Inertia component stubs). |
| `PAGE_NAME` | Studly-case page name (Inertia page stubs). |
| `EVENTNAME`, `SHORTEVENTNAME` | Fully qualified and short name of the `--event` class. |
| `TABLE` | Table name parsed from the migration name. |
| `FIELDS`, `FIELDS_UP`, `FIELDS_DOWN` | Column definitions rendered from `--fields`. |
| `PROVIDERS` | The `EventServiceProvider` and `RouteServiceProvider` class lines of the main provider. |
| `MODULE` | Module name. |
| `WEB_ROUTES_PATH`, `API_ROUTES_PATH` | Route file paths from `stubs.files`, with a leading `/`. |
| `QUOTE` | A random inspiring quote (view stubs). |

## Conditional sections

A conditional section is a block of lines between two marker lines:

```text
%%START_FACTORY%%
    use HasFactory;
%%END_FACTORY%%
```

- A marker is alone on its line. Spaces and tabs before and after it are allowed.
- The generator passes each section tag with a boolean. A tag that is `true` removes the two marker lines and keeps the lines between them. A tag that is `false` removes the markers and the lines between them.
- A tag the generator does not pass is not processed, and its markers stay in the output.
- Sections can nest and repeat. A tag applies to every block of that tag in the stub.

Sections the package uses:

| Stub | Tags |
| --- | --- |
| `model.stub` | `ATTRIBUTES` (true with `--attributes`), `FACTORY` (true with `--factory`), `PROPERTIES` (true without `--attributes`) |
| `route-provider.stub` | `WEB_ROUTES`, `API_ROUTES`: true when `stubs.files` lists `routes/web` or `routes/api` and the `routes` generator entry has `generate` set to `true` |

## Stub lookup

The `stubs.*` config keys are described in [Configuration](/laravel-modules/reference/configuration/#stubs).

The `Stub` class resolves a stub as `<base path>/<stub path>`. It uses the package stub when that file does not exist. The base path is `stubs.path` when `stubs.enabled` is `true`, and the package stub folder otherwise. The package sets the base path once, after the application boots.

## Publishing

`php artisan vendor:publish --tag=stubs` copies the package stubs to `stubs/nwidart-stubs` in the application. See [vendor:publish tags](/laravel-modules/reference/commands/publish/#vendorpublish-tags). The tag does not change `stubs.enabled` or `stubs.path`.

## Custom replacement classes

A replacement class computes the value of a placeholder when `module:make` renders a stub.

### Contract

```php
namespace Nwidart\Modules\Support;

abstract class ReplacementKeyCommand
{
    public function __construct(protected ModuleGenerator $generator) {}

    abstract public function handle(): string;
}
```

- `module:make` creates the object with the running `ModuleGenerator` and calls `handle()`.
- The returned string replaces the token. It is inserted as returned, without escaping. In a JSON stub, the class must escape the value itself. A stub that renders invalid JSON stops `module:make` before it writes anything.
- `ModuleGenerator::getName()` returns the studly-case module name.

### Registration

An entry of a `stubs.replacements` list that has an upper-case token as its key and a class name as its value selects a replacement class. Built-in names can stay in the same list.

```php
'stubs' => [
    'replacements' => [
        'composer' => [
            'LOWER_NAME',
            'STUDLY_NAME',
            'APP_URL' => \Modules\Support\Replacements\AppUrl::class,
        ],
    ],
],
```

- The class must extend `ReplacementKeyCommand`. A string key whose value is another class, or no class, prints `Replacement [<key>] for stub [<stub key>] maps to [<value>], which is not a ReplacementKeyCommand class. It was left empty.`
- Replacement classes apply to the stubs rendered by `module:make`. The `module:make-*` generators do not read `stubs.replacements`.

### Generating a class

`module:make-replacement <name> [<module>]` writes a class into the `command_replacements` path (default `app/Console/Replacements`). See [Generator commands](/laravel-modules/reference/commands/generators/#modulemake-replacement). The stub `replacement.stub` generates this body:

```php
class AppUrl extends ReplacementKeyCommand
{
    public function handle(): string
    {
        return $this->generator->getModule()->getLowerName();
    }
}
```

When `module:make` runs this body, it throws a `TypeError`. `ModuleGenerator::getModule()` is declared to return a `Module`, but `module:make` gives the generator the repository. The generated body is therefore a placeholder to replace. A body that works uses a method that `ModuleGenerator` really provides, for example `getName()`:

```php
public function handle(): string
{
    return strtolower($this->generator->getName());
}
```
