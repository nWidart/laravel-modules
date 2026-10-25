# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
1. Create a new branch
1. Code, test, commit and push
1. Open a pull request detailing your changes.

## Guidelines

* Format the code with `composer lint` (Pint).
* Run `composer check` before opening a pull request.
* Pull requests should be accompanied by passing tests.
* Please remember ensure you commit to the correct major version, IE v11 for Laravel 11.
* Commit messages must follow [Conventional Commits](#commit-messages).

## Setup

Clone your fork, then install the dev dependencies:
```bash
composer install
```
## Code style

Format the code with [Pint](https://laravel.com/docs/pint):
```bash
composer lint
```
CI runs Pint on every pull request. On pull requests from this repository it commits the fixes for you.

To check style without changing files, run `composer lint:check`.

## Before opening a pull request

```bash
composer check
```
This runs `composer lint:check`, `composer analyse`, and `composer test`. CI runs the same checks.

## Tests

Run all tests:
```bash
composer test
```

Run static analysis with PHPStan:
```bash
composer analyse
```

Rewrite the snapshot files after an intended change to generated output, then review the diff:
```bash
composer update-snapshots
```
To limit it to one test file, run `composer update-snapshots -- tests/Commands/Make/ControllerMakeCommandTest.php`.

Check coverage (needs Xdebug or PCOV). The HTML report is written to `build/coverage`:
```bash
composer test-coverage
```

## Commit messages

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/). CI checks the subject line of every commit in a pull request (merge commits are skipped). Releases and the CHANGELOG are generated from these messages, see [RELEASING.md](RELEASING.md).

Format: `type(scope): description`. The scope is optional and lowercase.

| Type | Use for | Release |
| --- | --- | --- |
| `feat` | A new feature, command, or option | minor |
| `fix` | A bug fix | patch |
| `perf` | A performance improvement | patch |
| `refactor` | A code change that is neither a fix nor a feature | patch |
| `docs` | Documentation only | patch |
| `test` | Tests only | patch |
| `build` | Composer, dependencies, packaging | patch |
| `ci` | GitHub Actions workflows | patch |
| `chore` | Other maintenance | patch |
| `style` | Formatting only | patch |
| `revert` | Reverting an earlier commit | patch |

Examples:

```text
feat(make): add module:make-foo command
fix(migrations): honour --pretend on module:migrate-rollback
docs: explain the activator config
test(commands): cover module:enable with --all
revert: feat(make): add module:make-foo command
```

A breaking change adds `!` after the type or scope, for example `feat(config)!: rename the scan.paths key`. Describe it in a `BREAKING CHANGE:` footer in the commit body. Major versions follow Laravel majors, so breaking changes are only released with a new Laravel major.

To fix commit messages already pushed to your branch, reword them with `git rebase -i` and force-push.
