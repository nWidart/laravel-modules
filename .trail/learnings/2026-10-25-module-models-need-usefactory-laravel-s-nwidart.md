---
title: "Module models need #[UseFactory]: Laravel's factory guess cannot reach module namespaces"
author: Nwidart
date: 2026-10-25
branch: fix/model-use-factory
cost: { minutes: 1, tokens: 2483 }
id: l-fd675361
scope: repo
status: active
src: agent
touches:
  - src/Commands/Make/ModelMakeCommand.php
  - src/Commands/stubs/model.stub
---

## Decided
- Laravel's Factory::resolveFactoryName() maps Modules\Blog\Models\Post to Database\Factories\Modules\Blog\Models\PostFactory, so HasFactory::factory() never finds a module factory. module:make-model -f now emits #[UseFactory(XFactory::class)]. A global Factory::guessFactoryNamesUsing() would also affect the host app, so it was not used.
- The factory FQCN comes from FactoryMakeCommand::getClassNamespace(), not a hardcoded Database\Factories, so custom modules.namespace and factory generator namespace stay in sync with module:make-factory.
- Without -f the model has no HasFactory, matching Laravel's own make:model stub.
- Optional stub blocks use Stub::sections() (%%START_X%%/%%END_X%%), because an empty placeholder leaves blank lines.

## Watch out
- --all must set its options before parent::handle(), or the model is rendered before --factory is true.
- module:make-factory with nested names (Admin/Post) writes invalid PHP (class Admin/PostFactory); this was already broken on master.
