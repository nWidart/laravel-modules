---
title: Stub placeholders are escaped only in JSON stubs; PHP string contexts escape in the command
author: Nwidart
date: 2026-10-25
branch: feat/stubs-laravel-13
cost: { minutes: 1, tokens: 2483 }
id: l-e26abe6c
scope: repo
status: active
src: agent
touches:
  - src/Commands/Make/CommandMakeCommand.php
  - src/Commands/stubs/command.stub
---

## Decided
- #2213 escapes placeholder values only for JSON stubs (ModuleGenerator). A placeholder inside a PHP string literal must be escaped by its generator. CommandMakeCommand applies addcslashes($name, "\\'") because command.stub puts $COMMAND_NAME$ in #[Signature('...')].

## Watch out
- A published command.stub that wraps $COMMAND_NAME$ in double quotes would show literal \' after this change. The shipped stub uses single quotes.
- Signature braces need no escaping.
