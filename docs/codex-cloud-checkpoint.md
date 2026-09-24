# Codex Cloud setup checkpoint

Date: 2026-09-24. Repository: `nushooGit/Rentier`. Connection: Rentier.
Branch: `chore/codex-cloud-setup`. Base: `19df3b459ab0792ad7152554b367433972b3eac1` (`main`).
The commit containing this checkpoint identifies the prepared source; consult the PR head for subsequent evidence updates.

## Completed preparation

- Read project rules, README, architecture, roadmap, issue register, development/E2E and deployment documentation; inspected both dependency manifests/locks, PHPUnit, Playwright and CI configuration.
- Added guarded setup and validation scripts, manual cloud instructions, and a dedicated read-only CI job. No application logic, authentication, financial calculations, production configuration or existing lockfiles changed.
- Existing open PR #2 is a Dependabot GitHub Actions update and is untouched. There is no `develop` branch. `sprint/old-backlog-a1` was not modified.
- Baseline `main` CI was green: tests run `35356609334`, linter run `35356609394`. These are historical CI results, not tests executed by this task.
- Repository deployment configuration reviewed: Coolify is documented to track `main`; repository workflows have no deploy step. External preview/webhook configuration could not be confirmed. No merge/deploy performed.

## Actual local execution

This Work session is an isolated scratch runtime, not a Codex Cloud environment.

| Command | Exit | Result |
|---|---:|---|
| `php -v` | 127 | `php: not found` |
| `composer --version` | 127 | `composer: not found` |
| `node --version` | 0 | v22.23.3, installed in a temporary tooling prefix; original runtime was v24.19.0 |
| `npm --version` | 0 | 11.9.0 |
| `composer install --no-interaction --prefer-dist` | 127 | Composer missing |
| `npm ci --no-audit --no-fund` | 0 | 433 packages installed using the existing lock, before switching from Node 24 to Node 22; not redundantly reinstalled |
| `php artisan test` | 127 | PHP missing; no tests/assertions executed, no application test failures established |
| `composer run types:check` | 127 | Composer missing |
| `npm run types:check` | 2 | 75 TS2307 errors for missing generated Wayfinder route/action modules |
| `npm run lint:check` | 1 | 41 import/order errors; rerun after Wayfinder generation before attributing to application defects |
| `npm run format:check` | 0 | All matched files use Prettier style |
| `npm run build` | 1 | Wayfinder calls `php artisan wayfinder:generate --with-form`; PHP missing |
| `composer run lint:check` | 127 | Composer/Pint unavailable |
| `bash -n scripts/codex/*.sh` | 0 | Shell syntax passed |
| `git diff --check` | 0 | No whitespace errors |
| `bash scripts/codex/setup.sh` | 1 | Correctly stops at missing PHP preflight; no APP_KEY or SQLite database created locally |
| Setup with dummy inherited `DB_URL` | 1 | Correctly rejects the variable before any application execution |

The attempt to provision PHP using `apt-get update` failed at the runtime privilege boundary (`setgroups`/`seteuid`, method exit 112). No sandbox protections were bypassed. npm also emitted environment proxy configuration warnings, but installation and Prettier succeeded.

## Outstanding validation

- Run the dedicated PR CI job and record its actual results. A green CI run is evidence for GitHub's runner only.
- Direct cloud administration is not exposed to this session; cloud environment creation, automatic setup binding and cloud execution remain unconfirmed.
- No Playwright run: isolated launcher currently depends on PowerShell.
- The Ubuntu OS-runtime bootstrap documented for Cloud is not executed or verified here.

## Exact next task

Finish initial environment validation only: inspect the PR's dedicated bootstrap/validation job, fix environment-script issues if any, then create/select the dedicated Codex Cloud environment with the manual settings in `docs/codex-cloud.md` and execute `bash scripts/codex/validate.sh` on this branch. Record actual runtime versions, test/assertion counts and every exit code. Keep the PR unmerged, and stop; do not implement backlog features.
