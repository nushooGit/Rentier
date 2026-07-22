# Rentier beta deploy readiness

This is a practical checklist for a future private beta deploy on a VPS. It is not a deployment runbook for a live production launch and it does not include real credentials.

## Recommended server stack

- Linux VPS with SSH access and automated security updates.
- Nginx or Apache pointing the web root to `public/`.
- PHP 8.4.1+ with common Laravel extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pgsql`, `pdo_pgsql`, `session`, `tokenizer`, `xml`.
- Composer 2.
- Node.js LTS and npm for building assets during deploy, or build artifacts produced in CI.
- PostgreSQL for beta production. SQLite remains the local development default.
- Supervisor or systemd for `php artisan queue:work`.
- Cron entry for Laravel scheduler.
- HTTPS certificate, usually via Certbot or the VPS provider.

## Environment variables

Required beta values:

```dotenv
APP_NAME=Rentier
APP_ENV=production
APP_KEY=base64:generated-with-php-artisan-key-generate
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Europe/Bucharest
APP_LOCALE=ro
APP_FALLBACK_LOCALE=en
RENTIER_REGISTRATION_ENABLED=false

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=DB_NAME
DB_USERNAME=DB_USER
DB_PASSWORD=DB_PASSWORD_PLACEHOLDER
DB_SSLMODE=prefer

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=MAIL_USERNAME_PLACEHOLDER
MAIL_PASSWORD=MAIL_PASSWORD_PLACEHOLDER
MAIL_FROM_ADDRESS=no-reply@your-domain.example
MAIL_FROM_NAME="${APP_NAME}"

PASSKEYS_USER_HANDLE_SECRET=base64-or-random-secret
VITE_APP_NAME="${APP_NAME}"
```

Notes:

- Generate `APP_KEY` on the server with `php artisan key:generate --show`, then place it in `.env`.
- `PASSKEYS_USER_HANDLE_SECRET` can be a separate random secret. If omitted, the app falls back to `APP_KEY`, but a dedicated value is cleaner for beta.
- `RENTIER_REGISTRATION_ENABLED=false` disables public signup routes. Keep it disabled for private beta unless public signups are intentionally allowed.
- Keep `.env` outside version control.
- Production beta should use PostgreSQL. Keep credentials only in the server-side `.env`.
- `APP_URL` must use the HTTPS production domain so signed email verification, password reset, and invitation links are generated correctly.

## Hetzner PostgreSQL runbook

Use [Hetzner PostgreSQL Beta Deployment](hetzner-deployment.md) for the focused Ubuntu 24.04, Nginx, PostgreSQL, queue, scheduler, backup, and smoke-test preparation checklist.

## First deploy checklist

1. Create the deploy user and application directory.
2. Clone the repository or upload the release artifact.
3. Install PHP dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

4. Install and build frontend assets:

```bash
npm ci
npm run build
```

5. Create `.env` from `.env.example` and set production values.
6. Generate or set `APP_KEY`.
7. Run database migrations:

```bash
php artisan migrate --force
```

8. Create the public storage symlink:

```bash
php artisan storage:link
```

9. Cache production configuration:

```bash
php artisan optimize
```

10. Configure the web server root to `public/`.
11. Configure HTTPS and set `APP_URL` to the HTTPS domain.
12. Configure queue worker process:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

13. Configure the scheduler cron with non-blocking `flock` and an application-owned log path:

```cron
* * * * * DEPLOY_USER /usr/bin/flock -n /tmp/rentier-scheduler.lock /bin/bash -lc 'cd /var/www/YOUR_APP_PATH/current && /usr/bin/php artisan schedule:run >> /var/www/YOUR_APP_PATH/current/storage/logs/scheduler.log 2>&1'
```

## Permissions

The web server user and deploy user must be able to write to the application-owned runtime paths:

- `storage/`
- `storage/logs/`
- `bootstrap/cache/`

The web server should serve only `public/`. Do not expose the project root, `.env`, `storage/app/private`, or `database/`.

## Build output

`npm run build` writes Vite assets to `public/build`. The repository ignores this directory, so either build during deploy or upload the generated build artifact with each release. Ensure `public/hot` is not present on the VPS.

## Migrations and database

The app includes migrations for users, sessions, cache, queues, passkeys, teams, properties, renters, leases, rent payments, and expenses. Run migrations with `--force` on deploy. Do not run the local demo seeder on beta unless intentionally creating demo data.

## Queues, scheduler, and mail

- Team invitation notifications implement `ShouldQueue`, so beta needs a queue worker when `QUEUE_CONNECTION=database`.
- Password reset and email verification mail require a real mail transport for beta.
- The scheduler deletes expired team invitations daily. Configure cron before beta.

## Storage

Current code does not show user file uploads, but Laravel's public disk is configured. Run `php artisan storage:link` so future public storage URLs work and so the deploy state matches Laravel expectations.

## Backups

Before beta, set up automated backups for:

- Application database, at least daily.
- `.env` and server configuration, stored securely.
- `storage/app/public` if file uploads are introduced.

Test restore into a separate database before inviting beta users.

## Rollback notes

- Keep the previous release directory or git commit available.
- Put the app in maintenance mode for risky rollback work:

```bash
php artisan down
```

- Restore the previous code release and run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize
php artisan up
```

- Avoid `migrate:rollback` after real beta data unless the specific migration has been reviewed for data loss. Prefer restoring a verified database backup when schema changes cannot be safely reversed.

## Post-deploy smoke test

1. Visit `/up` and confirm healthy response.
2. Visit `/` and confirm frontend assets load without Vite dev server.
3. Confirm `/register` is unavailable when `RENTIER_REGISTRATION_ENABLED=false`, or register a test account only if public signup is intentionally enabled.
4. Confirm email verification and password reset mail are delivered.
5. Confirm redirect to `/{team}/dashboard` works after auth.
6. Create, view, edit, and delete a test property.
7. Create a test lease, rent payment, and expense in the same workspace.
8. Confirm dashboard cards and payment method breakdown render.
9. Invite a test user to a team and confirm the queued invitation email is sent.
10. Confirm scheduler and queue worker logs are clean.
11. Confirm `APP_DEBUG=false` by checking that errors do not show stack traces.

## Beta blockers and decisions

- Keep `RENTIER_REGISTRATION_ENABLED=false` for private beta unless public registration is intentionally opened.
- Email verification is required for protected app routes. Ensure users receive verification emails before inviting real beta users.
- Use PostgreSQL for the beta database. `.env.example` defaults to SQLite for local development only.
- Configure a real mail provider before using invitations, password reset, or email verification.
- Run a persistent queue worker because team invitation notifications are queued.
- Configure cron for the scheduled expired-invitation cleanup.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, and `SESSION_SECURE_COOKIE=true` for HTTPS beta.
- Confirm HTTPS, domain DNS, firewall, and web-server `public/` root before exposing the app.
- Add and test database backups before inviting beta users.
