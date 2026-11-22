---
title: Contribute and get help
description: Where to ask questions, report bugs and security problems, contribute code or documentation, and sponsor the project.
---

## Get help

- Ask a question in [GitHub Discussions](https://github.com/nWidart/laravel-modules/discussions).
- Report a bug in [GitHub Issues](https://github.com/nWidart/laravel-modules/issues). Include the package version, the Laravel version, the command you ran and the output.

## Report a security problem

The repository has no `SECURITY.md`, and private vulnerability reporting is not enabled on it. Do not post the details of a vulnerability in a public issue. Contact the maintainer, [Nicolas Widart](https://github.com/nwidart), through the contact details on their GitHub profile, and say that the message concerns a security problem.

Security fixes go to the current major and to `12.x`. See [Version compatibility](/laravel-modules/upgrading/compatibility/).

## Contribute code

Read [CONTRIBUTING.md](https://github.com/nWidart/laravel-modules/blob/master/CONTRIBUTING.md) first. In short:

1. Fork the repository and branch from the branch that matches your Laravel version (`master` for Laravel 13).
2. Run `composer check` before you open a pull request. It runs the style check, static analysis and the tests.
3. Write commit messages as [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/), for example `fix(migrations): honour --pretend on module:migrate-rollback`. CI checks every commit, and the changelog and version number come from them.

## Contribute to this documentation

Each page has an edit link at the bottom that opens the Markdown file on GitHub. To run the site locally, see [docs/README.md](https://github.com/nWidart/laravel-modules/blob/master/docs/README.md). A change to user-facing behaviour should update the matching page in the same pull request.

## Sponsor the project

The repository lists two sponsor accounts in [`.github/FUNDING.yml`](https://github.com/nWidart/laravel-modules/blob/master/.github/FUNDING.yml): [nwidart](https://github.com/sponsors/nwidart) and [dcblogdev](https://github.com/sponsors/dcblogdev).
