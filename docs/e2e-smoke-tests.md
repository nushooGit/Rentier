# E2E smoke tests

Rentier uses Playwright for browser-level smoke tests that can run against local development, staging, or a future VPS beta URL. These tests are intentionally non-destructive: they do not reset, migrate, or wipe the database.

## Install browsers

```bash
npx playwright install chromium
```

## Required environment

Authenticated tests read:

```bash
E2E_EMAIL=e2e@rentier.test
E2E_PASSWORD=password
```

The default base URL is `http://127.0.0.1:8000`. Override it with:

```bash
E2E_BASE_URL=http://127.0.0.1:8000
```

For staging or VPS beta:

```bash
E2E_BASE_URL=https://staging-domain.example
E2E_EMAIL=e2e@rentier.test
E2E_PASSWORD=change-me
```

Do not use real production credentials. Do not run smoke tests against production unless the test account and generated `E2E Smoke` records are explicitly acceptable there.

## Prepare a verified E2E user

The user must be email-verified because protected app routes require verified email.

For local development, create or update a verified user with Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::firstOrCreate(
    ['email' => 'e2e@rentier.test'],
    ['name' => 'E2E Smoke User', 'password' => 'password']
);

$user->forceFill([
    'name' => 'E2E Smoke User',
    'password' => 'password',
    'email_verified_at' => now(),
])->save();

if (! $user->personalTeam()) {
    app(App\Actions\Teams\CreateTeam::class)->handle($user, 'E2E Smoke Workspace', isPersonal: true);
}
```

For staging, create a dedicated verified beta smoke user through the normal admin/database process. Keep the credentials in environment variables or CI secrets.

## Run locally

If `E2E_BASE_URL` is omitted, Playwright starts the local app with `composer run dev` and waits for `http://127.0.0.1:8000/up`.

```powershell
$env:E2E_EMAIL='e2e@rentier.test'
$env:E2E_PASSWORD='password'
npm run test:e2e
```

To use an already running local app, start it separately:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Then run:

```bash
E2E_BASE_URL=http://127.0.0.1:8000 E2E_EMAIL=e2e@rentier.test E2E_PASSWORD=password npm run test:e2e
```

On Windows PowerShell:

```powershell
$env:E2E_BASE_URL='http://127.0.0.1:8000'
$env:E2E_EMAIL='e2e@rentier.test'
$env:E2E_PASSWORD='password'
npm run test:e2e
```

## Scripts

- `npm run test:e2e` runs Chromium smoke tests.
- `npm run test:e2e:isolated` selects the existing PowerShell launcher on Windows or `scripts/e2e/start-isolated.sh` on Linux/macOS. It resets only guarded `database/e2e.sqlite`, seeds the verified E2E user, starts Laravel on `http://127.0.0.1:8010`, starts Vite on port `5174`, and runs the authenticated Playwright suite.
- `npm run test:e2e:headed` runs Chromium visibly for debugging.
- `npm run test:e2e:ui` opens Playwright UI mode.

## Isolated local E2E

The isolated command uses `.env.e2e`, created from `.env.e2e.example` when missing. It is local-only and is safe to reset because it points at `database/e2e.sqlite`, not the normal local database.

On Windows, keep using `npm run test:e2e:isolated`; Playwright starts the PowerShell launcher. On Linux (including Codex Cloud), the same command starts `bash scripts/e2e/start-isolated.sh`. The Linux launcher refuses inherited `DB_URL`/`DATABASE_URL`, supplies only a local E2E URL and delegates database-reset safety to Laravel's guarded `e2e:bootstrap` command. Never copy production `.env` or use actual account credentials in this workflow.

```powershell
npm run test:e2e:isolated
```

The bootstrap command is guarded and refuses to run unless:

- `APP_ENV` is exactly `e2e`.
- `DB_CONNECTION` is exactly `sqlite`.
- `DB_DATABASE` resolves to **exactly** `database/e2e.sqlite`, without alternate E2E-named files, path traversal or symlinks.
- There is no external `DB_URL` for the SQLite connection.
- `APP_URL` is `localhost` or `127.0.0.1` and does not contain `rentier.ro`.

The seeded isolated account defaults to:

```text
E2E_EMAIL=e2e@rentier.test
E2E_PASSWORD=password
```

Do not point write-capable E2E tests at staging, beta, production, or `rentier.ro`. The write-capable tests also reject non-local `E2E_BASE_URL` values.

## Browser availability in Codex Cloud

The first Linux Codex run verified the launcher and discovered **9 tests in 3 files**. The E2E scenarios did **not** execute: downloading Playwright's Chromium build 1228 returned HTTP 403, with and without `NODE_USE_ENV_PROXY=1`. This is an environment blocker, not a passing E2E run. Install Chromium only in the isolated test runtime where its exact Playwright browser build can be fetched or is already available; then rerun `npm run test:e2e:isolated` and record the actual passing/failing scenario counts. Never run the write-capable isolated suite against production.

### Verified isolated Linux CI run (2026-09-24)

GitHub Actions [Isolated Playwright (Linux), run 36045489128](https://github.com/nushooGit/Rentier/actions/runs/36045489128) installed Playwright's pinned Chromium build 1228, launched the guarded local SQLite E2E application, used previously built Vite assets (without an HMR server under `CI=true`), and ran **9/9 Playwright tests successfully in 36.0 seconds**. This is verified on a disposable Linux GitHub runner, not in Codex Cloud and not on production. The prior Codex Cloud HTTP 403 for Chromium remains a separate environment limitation.

## Coverage

Current smoke tests cover:

- Home page loads.
- Login page loads.
- Registration page/link behavior works when registration is enabled or disabled.
- Login with a verified E2E user reaches the dashboard.
- Core landlord workflow creates an `E2E Smoke` property, active contract, rent payment, guarantee payment, and expense.
- Dashboard still loads after the core flow.
- PAY-03 rent allocation in isolated mode: multiple payments rolling forward, edit/delete recalculation, partial future advances, lease-end unallocated credit, and guarantee separation.

## Failure artifacts

Playwright keeps screenshots, traces, and videos for failed tests. Inspect the HTML report with:

```bash
npx playwright show-report
```

Open a retained trace with:

```bash
npx playwright show-trace path/to/trace.zip
```
