---
title: Dependabot keeps library-style version ranges and never proposes a new Laravel major
author: Nwidart
date: 2026-10-28
branch: chore/dependabot-config
cost: { minutes: 3, tokens: 61766 }
id: l-d6fb08c7
scope: repo
status: active
src: agent
touches:
  - ".github/dependabot.yml"
  - cliff.toml
  - ".github/workflows/php.yml"
---

## Decided
- Dependabot runs weekly for Composer (root manifest only) and GitHub Actions, with a 7-day cooldown.
- Version ranges stay on the default strategy, which widens ranges for a library (`^2.1 || ^3.0`).
- Major updates of laravel/framework and orchestra/testbench are ignored. The package major tracks the Laravel major, so a new Laravel major is a manual release, not a Dependabot PR.
- Commit prefix `chore` with scope, so PRs are `chore(deps)` / `chore(deps-dev)`. The changelog config and the Conventional Commits CI job already skip those, so nothing else needed changing.

## Tried and ruled out
- `versioning-strategy: increase`: it raises the minimum version of runtime requirements, which forces the new minimum on every app using the package. Wrong for a library.
- Grouping Composer updates: without a lock file Dependabot only opens PRs for versions outside the range (majors), and one failing major in a group would block the others.

## Watch out
- A PHPUnit major PR can fail CI until orchestra/testbench supports it. Close it or wait; do not widen testbench by hand.
- Security updates ignore the cooldown and only target the default branch, so 12.x security fixes stay manual.

In the code:
- .github/dependabot.yml (ignore list, cooldown.default-days: 7, commit-message prefix)
- cliff.toml commit_parsers skip `^(chore|build)\(deps(-dev)?\)` and author dependabot[bot]
- .github/workflows/php.yml Conventional Commits job skips dependabot[bot] PRs
