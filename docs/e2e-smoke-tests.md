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
- `npm run test:e2e:headed` runs Chromium visibly for debugging.
- `npm run test:e2e:ui` opens Playwright UI mode.

## Coverage

Current smoke tests cover:

- Home page loads.
- Login page loads.
- Registration page/link behavior works when registration is enabled or disabled.
- Login with a verified E2E user reaches the dashboard.
- Core landlord workflow creates an `E2E Smoke` property, active contract, rent payment, guarantee payment, and expense.
- Dashboard still loads after the core flow.

## Failure artifacts

Playwright keeps screenshots, traces, and videos for failed tests. Inspect the HTML report with:

```bash
npx playwright show-report
```

Open a retained trace with:

```bash
npx playwright show-trace path/to/trace.zip
```
