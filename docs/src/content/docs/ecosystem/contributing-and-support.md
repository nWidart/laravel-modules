---
title: Contribute and get help
description: Where to ask questions, report bugs and security problems, contribute code or documentation, and sponsor the project.
---

## Get help

- Ask a question in [GitHub Discussions](https://github.com/nWidart/laravel-modules/discussions).
- Report a bug in [GitHub Issues](https://github.com/nWidart/laravel-modules/issues). Include the package version, the Laravel version, the command you ran and the output.

## Report a security problem

Use the [private report form](https://github.com/nWidart/laravel-modules/security/advisories/new). You can also open it from the repository's Security tab with "Report a vulnerability". Do not post the details in a public issue, discussion or pull request. The full policy is in [SECURITY.md](https://github.com/nWidart/laravel-modules/blob/master/SECURITY.md).

A security fix lands on `master` and, when the problem exists in version 12, on `12.x`. See [Version compatibility](/laravel-modules/upgrading/compatibility/).

## Contribute code

Read [CONTRIBUTING.md](https://github.com/nWidart/laravel-modules/blob/master/CONTRIBUTING.md) first. In short:

1. Fork the repository and branch from `master`. Changes target `master`. `12.x` accepts security fixes only.
2. Run `composer check` before you open a pull request. It runs the style check, static analysis and the tests.
3. Write commit messages as [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/), for example `fix(migrations): honour --pretend on module:migrate-rollback`. CI checks every commit, and the changelog and version number come from them.

## Contribute to this documentation

To add a package to the [community packages](/laravel-modules/ecosystem/community-packages/) list, open a pull request that changes that page. Each page has an edit link at the bottom that opens the Markdown file on GitHub. To run the site locally, see [docs/README.md](https://github.com/nWidart/laravel-modules/blob/master/docs/README.md). Update the matching documentation page in the same pull request whenever you change user-facing behaviour.

## Sponsor the project

The repository lists two sponsor accounts in [`.github/FUNDING.yml`](https://github.com/nWidart/laravel-modules/blob/master/.github/FUNDING.yml): [nwidart](https://github.com/sponsors/nwidart) and [dcblogdev](https://github.com/sponsors/dcblogdev).
