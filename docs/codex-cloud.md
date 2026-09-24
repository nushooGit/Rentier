# Codex Cloud development environment

Scope: `nushooGit/Rentier` only, using the **Rentier** GitHub connection. This is a disposable development environment, never a production deployment.

## Runtime and setup

Required tools:

- PHP 8.4.1+ (use the 8.4 series, as exercised by the dedicated CI job), Composer 2.
- PHP extensions required by `composer.lock`, plus PDO SQLite/PDO PostgreSQL, bcmath, curl and zip. The setup script derives the locked extension requirements and fails with the missing names. An explicit SQLite-only Codex mode permits **only** `ext-pdo_pgsql` to be absent.
- Node **22.12+ within major 22**, npm, Bash and standard Linux utilities. Locked Vite 8.1.0 requires at least Node 22.12.0.
- SQLite through PHP's PDO driver; no PostgreSQL server is needed.

`scripts/codex/setup.sh` verifies these prerequisites; it does not install OS runtimes. It uses `composer install` and `npm ci`, generates an independent random APP_KEY in an ignored `.env` derived from `.env.example`, creates a private `/tmp/rentier-codex.*/database.sqlite`, and runs forward migrations only against that SQLite database. Mail stays in logs, sessions/cache use files, and queues run synchronously. No production variables or secrets are required.

It refuses inherited application/database/mail variables, cached Laravel configuration, unmanaged or edited `.env` files, and missing managed databases. Reuse a managed environment by rerunning setup; existing keys and data are preserved. If the temporary database disappeared, reset the cloud cache/use a fresh disposable checkout. Do not adapt a production checkout.

### Preinstalled phpenv PHP without PDO PostgreSQL

Check `php --ini` and `php -i | grep extension_dir` before attempting to enable an extension: it must be compiled for the **active phpenv PHP**, not installed for Ubuntu's unrelated system PHP. If the matching extension is already present and can be enabled reproducibly, prefer that and keep the normal setup command. If it is absent, use the deliberate SQLite-only opt-in below. This option does not change `composer.json` or `composer.lock`, install any new packages, or permit a production or externally hosted database.

```bash
RENTIER_CODEX_SQLITE_ONLY=1 bash scripts/codex/setup.sh
```

The script still requires PDO SQLite and every other PHP extension. It passes `--ignore-platform-req=ext-pdo_pgsql` only to `composer install`, then checks Composer's full machine-readable platform report and accepts a missing `ext-pdo_pgsql` entry only. An unset or non-`1` opt-in keeps the full PostgreSQL requirement. The guard rejects inherited application/database settings, Composer-wide platform-ignore variables, unmanaged `.env` files, and any managed environment that is not local SQLite under `/tmp/rentier-codex.*`.

The setup build generates ignored Wayfinder route/action types and the Vite manifest before TypeScript, ESLint and feature tests. The normal build also fetches Instrument Sans through the existing Bunny font integration; network access may be needed for its first build.

## Verified Codex Cloud configuration (2026-09-24)

The dedicated `rentier-development` environment has been created for **only** `nushooGit/Rentier`. The owner validated it with PHP 8.4 (phpenv) and Node 22. The PHP runtime lacks `pdo_pgsql`, so disposable Codex tasks use the deliberately scoped SQLite-only mode. Initial setup and validation succeeded after the changes below; see the [checkpoint](codex-cloud-checkpoint.md) and GitHub CI for precise evidence.

In [Codex environment settings](https://chatgpt.com/codex/settings/environments), choose the Universal image, PHP 8.4 and Node 22. Use manual setup; do not add application/database variables or production secrets. Select the intended branch **when creating each Codex task**. Codex checks out the selected branch automatically; the setup container did not expose a Git remote named `origin`, so do **not** run `git fetch origin` or switch branches in the setup script. Before the approved merge, select `chore/codex-cloud-setup`; after merge, the setup scripts will exist on `main`.

Use this setup script for the verified phpenv runtime (the `COMPOSER_ALLOW_SUPERUSER` exception is only for Codex's isolated container):

```bash
set -euo pipefail
export COMPOSER_ALLOW_SUPERUSER=1
export NODE_USE_ENV_PROXY=1
test -f scripts/codex/setup.sh
RENTIER_CODEX_SQLITE_ONLY=1 bash scripts/codex/setup.sh
```

Maintenance script for branches containing the setup scripts:

```bash
COMPOSER_ALLOW_SUPERUSER=1 NODE_USE_ENV_PROXY=1 RENTIER_CODEX_SQLITE_ONLY=1 bash scripts/codex/setup.sh
```

Run validation within the task, without reinstalling the dependencies:

```bash
NODE_USE_ENV_PROXY=1 RENTIER_CODEX_SQLITE_ONLY=1 bash scripts/codex/validate.sh
```

Inspect `.codex-local/validation/results.tsv` for actual exit codes. The script runs PHPStan with `--memory-limit=1G`; the cloud runtime's default 128 MiB was insufficient. The setup build fetches Instrument Sans from Bunny Fonts; `NODE_USE_ENV_PROXY=1` allowed it to use the sandbox proxy. Keep agent internet disabled unless an uncached asset requires a narrowly scoped host allowlist. Container caching may be enabled only after verifying that its resumed workspace retains the managed temporary SQLite database; otherwise use a fresh container with caching off.

Do not use the SQLite-only override on production, change any live credentials, or merge into `main` before checking Coolify auto-deployment and preview settings. The cloud environment is independent of production. OpenAI's [cloud environment documentation](https://learn.chatgpt.com/docs/environments/cloud-environment) describes setup, caching and agent internet behavior.

## Validation and CI

```bash
# On a complete CI PHP runtime with pdo_pgsql:
bash scripts/codex/setup.sh
bash scripts/codex/validate.sh

# On the preinstalled Codex phpenv PHP runtime without pdo_pgsql:
NODE_USE_ENV_PROXY=1 RENTIER_CODEX_SQLITE_ONLY=1 bash scripts/codex/validate.sh
```

The validation script runs PHP/Composer/Node/npm version checks, `php artisan test`, PHPStan (`./vendor/bin/phpstan analyse --memory-limit=1G`), TypeScript, ESLint, Prettier, Vite build, and Pint (`composer run lint:check`). It records separate logs/exit codes and returns failure if any check fails. Installations are not repeated by validation. Setup's initial build prepares test assets; validation's final build is the requested build check.

`.github/workflows/codex-environment.yml` runs the same setup and validation in a fresh Ubuntu GitHub runner with PHP 8.4 and Node 22. It checks lockfiles remain unchanged. A green GitHub job validates preparation on that runner, **not creation or validation of a Codex Cloud environment**.

The normal CI job retains its full PDO PostgreSQL requirement. A separate CI job disables that extension, checks that normal setup refuses it, then tests the explicit SQLite-only opt-in and safety guards. Its validation now requires all 11 checks, including Pint, to pass; it fails on any check error.

Existing `tests.yml` runs PHP 8.4/8.5 and disposable PostgreSQL jobs. Existing `lint.yml` uses mutating format commands and `npm install`, so its success alone is not evidence that read-only checks pass on a clean tree. Those workflows are unchanged, including the open Dependabot PR #2.

Playwright was inspected but is outside this initial command suite. `test:e2e:isolated` currently starts PowerShell (`scripts/e2e/start-isolated.ps1`); it is not Linux-ready. Do not run browser tests against production. A later task can add a Linux launcher with the existing isolation guards.

## Deployment boundary

`docs/hetzner-deployment.md` documents Coolify tracking `main`. `nixpacks.toml` calls the existing production startup script; neither is changed here. Repository workflows contain no deployment steps. External Coolify preview/webhook settings were not inspected and cannot be inferred from GitHub. The new workflow is PR-only, read-only, and uses temporary SQLite. Target `main` because there is no active `develop` branch; never merge without a separate deployment review.

See [the checkpoint](codex-cloud-checkpoint.md) for actual outcomes and remaining work.
