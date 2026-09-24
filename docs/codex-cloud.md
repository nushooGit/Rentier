# Codex Cloud development environment

Scope: `nushooGit/Rentier` only, using the **Rentier** GitHub connection. This is a disposable development environment, never a production deployment.

## Runtime and setup

Required tools:

- PHP 8.4.1+ (use the 8.4 series, as exercised by the dedicated CI job), Composer 2.
- PHP extensions required by `composer.lock`, plus PDO SQLite/PDO PostgreSQL, bcmath, curl and zip. The setup script derives the locked extension requirements and fails with the missing names.
- Node **22.12+ within major 22**, npm, Bash and standard Linux utilities. Locked Vite 8.1.0 requires at least Node 22.12.0.
- SQLite through PHP's PDO driver; no PostgreSQL server is needed.

`scripts/codex/setup.sh` verifies these prerequisites; it does not install OS runtimes. It uses `composer install` and `npm ci`, generates an independent random APP_KEY in an ignored `.env` derived from `.env.example`, creates a private `/tmp/rentier-codex.*/database.sqlite`, and runs forward migrations only against that SQLite database. Mail stays in logs, sessions/cache use files, and queues run synchronously. No production variables or secrets are required.

It refuses inherited application/database/mail variables, cached Laravel configuration, unmanaged or edited `.env` files, and missing managed databases. Reuse a managed environment by rerunning setup; existing keys and data are preserved. If the temporary database disappeared, reset the cloud cache/use a fresh disposable checkout. Do not adapt a production checkout.

The setup build generates ignored Wayfinder route/action types and the Vite manifest before TypeScript, ESLint and feature tests. The normal build also fetches Instrument Sans through the existing Bunny font integration; network access may be needed for its first build.

## Manual Codex Cloud settings

No Codex Cloud management API is exposed to the preparation session, and its cloud browser is signed out of ChatGPT. Authentication is needed before cloud settings can be accessed. In [Codex environment settings](https://chatgpt.com/codex/settings/environments):

1. Create/select a dedicated Rentier environment. Select only `nushooGit/Rentier` through the Rentier connection; do not grant other repository access.
2. Choose the universal image. In **Set package versions**, select Node 22 and PHP 8.4 if offered. Verify PHP >= 8.4.1 and Composer 2 in the setup terminal. Do not add production secrets or application environment variables.
3. If PHP/extensions/Composer are missing in the Ubuntu disposable container, prepend this OS bootstrap to the setup field (requires the container's package-manager permissions and network):

   ```bash
   sudo apt-get update
   sudo apt-get install -y software-properties-common ca-certificates
   sudo add-apt-repository -y ppa:ondrej/php
   sudo apt-get update
   sudo apt-get install -y php8.4-cli php8.4-bcmath php8.4-curl php8.4-mbstring php8.4-pgsql php8.4-sqlite3 php8.4-xml php8.4-zip composer
   sudo update-alternatives --set php /usr/bin/php8.4
   ```

   These commands are **not verified in Codex Cloud**. They must run only in its disposable container; never on the VPS. If the selected image already provides the required runtime, omit them. A package-manager permission failure is an environment blocker; do not disable sandbox protections.
4. Disable automatic dependency setup/use manual setup so npm does not perform an unlocked install. For the initial unmerged PR, the scripts do not yet exist on the default branch. Use this temporary setup field to fetch the prepared branch explicitly:

   ```bash
   set -euo pipefail
   git fetch origin chore/codex-cloud-setup
   git switch --detach FETCH_HEAD
   bash scripts/codex/setup.sh
   ```

   After a separately approved merge, replace that field with `bash scripts/codex/setup.sh`. Do not merge just to configure the environment.
5. Set the maintenance script to `bash scripts/codex/setup.sh` for tasks on branches containing these scripts. It refreshes dependencies from their locks after a cached container switches branches. Do not repeat dependency installation in the task itself.
6. Start the first cloud task on `chore/codex-cloud-setup`, run `bash scripts/codex/validate.sh`, and inspect every exit code in `.codex-local/validation/results.tsv`. Validate the cloud runtime versions too. Setup has network access; if the final build cannot fetch uncached fonts in the agent phase, permit only the required asset hosts (`fonts.bunny.net`) through the environment's internet settings and rerun the failed check.
7. Keep the PR unmerged and stop after initial validation. Do not begin backlog work.

Cloud settings, caching, separate setup shell and internet behavior: [official OpenAI documentation](https://learn.chatgpt.com/docs/environments/cloud-environment). Shell exports in setup do not configure the later agent shell; choose runtime versions in environment settings.

## Validation and CI

```bash
bash scripts/codex/setup.sh
bash scripts/codex/validate.sh
```

The validation script runs PHP/Composer/Node/npm version checks, `php artisan test`, PHPStan (`composer run types:check`), TypeScript, ESLint, Prettier, Vite build, and Pint (`composer run lint:check`). It records separate logs/exit codes and returns failure if any check fails. Installations are not repeated by validation. Setup's initial build prepares test assets; validation's final build is the requested build check.

`.github/workflows/codex-environment.yml` runs the same setup and validation in a fresh Ubuntu GitHub runner with PHP 8.4 and Node 22. It checks lockfiles remain unchanged. A green GitHub job validates preparation on that runner, **not creation or validation of a Codex Cloud environment**.

Existing `tests.yml` runs PHP 8.4/8.5 and disposable PostgreSQL jobs. Existing `lint.yml` uses mutating format commands and `npm install`, so its success alone is not evidence that read-only checks pass on a clean tree. Those workflows are unchanged, including the open Dependabot PR #2.

Playwright was inspected but is outside this initial command suite. `test:e2e:isolated` currently starts PowerShell (`scripts/e2e/start-isolated.ps1`); it is not Linux-ready. Do not run browser tests against production. A later task can add a Linux launcher with the existing isolation guards.

## Deployment boundary

`docs/hetzner-deployment.md` documents Coolify tracking `main`. `nixpacks.toml` calls the existing production startup script; neither is changed here. Repository workflows contain no deployment steps. External Coolify preview/webhook settings were not inspected and cannot be inferred from GitHub. The new workflow is PR-only, read-only, and uses temporary SQLite. Target `main` because there is no active `develop` branch; never merge without a separate deployment review.

See [the checkpoint](codex-cloud-checkpoint.md) for actual outcomes and remaining work.
