# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
1. Create a new branch
1. Code, test, commit and push
1. Open a pull request detailing your changes.

## Guidelines

* Please ensure the coding style running `composer pcf`.
* * Pull requests should be accompanied by passing tests.
* Please remember ensure you commit to the correct major version, IE v11 for Laravel 11.

## Setup

Clone your fork, then install the dev dependencies:
```bash
composer install
```
## PHP CS Fixer

Run php-cs-fixer:
```bash
composer pcf
```
## Tests

Run all tests:
```bash
composer test
```

Check coverage:
```bash
composer test-coverage
```
