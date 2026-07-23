# Hetzner PostgreSQL Beta Deployment

This is a preparation runbook for the first private beta on an existing Hetzner VPS running Ubuntu 24.04 LTS. It intentionally uses placeholders and does not include real domains, IPs, users, passwords, API keys, SMTP credentials, or SSH keys.

Do not run these commands blindly on production. Review every placeholder before connecting to the VPS.

## Target Stack

- Ubuntu 24.04 LTS
- Nginx serving the Laravel `public/` directory
- PHP 8.4.1 or newer, matching `composer.json`; select the matching PHP-FPM socket in Nginx, such as `php8.4-fpm.sock` or a newer installed PHP-FPM socket
- Required PHP extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pgsql`, `pdo_pgsql`, `session`, `tokenizer`, `xml`
- Composer 2
- Node.js LTS and npm for `npm ci` and `npm run build`
- PostgreSQL
- Database-backed Laravel queue for the initial single-server beta
- Cron for Laravel scheduler
- HTTPS, usually through Certbot
- Timestamped PostgreSQL backups

Redis is not required for the initial beta. The repository has database queue, job batch, and failed job migrations.

## Coolify Nixpacks Runtime

The current beta deployment uses Coolify on the same Hetzner VPS, with PostgreSQL as a separate internal Coolify resource and the Laravel app built from Git with Nixpacks.

Repository runtime files:

- `nixpacks.toml`
- `deploy/coolify/start.sh`
- `deploy/coolify/nginx.template.conf`
- `deploy/coolify/php-fpm.conf`
- `deploy/coolify/supervisord.conf`
- `deploy/coolify/worker-*.conf`

Architecture inside the application container:

- Supervisor runs as PID 1.
- Nginx serves `/app/public` and forwards PHP requests to PHP-FPM on `127.0.0.1:9000`.
- PHP-FPM runs in the foreground with low-resource pool defaults.
- One queue worker runs `php /app/artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
- One scheduler process runs `php /app/artisan schedule:work`.

Coolify application settings:

- Build Pack: `Nixpacks`
- Branch: `main`
- Port Exposes: `80`
- Health check path: `/up`
- Start Command: leave empty after `nixpacks.toml` is committed. Do not keep `php artisan serve --host=0.0.0.0 --port=80`; it is only a temporary diagnostic command and is not the production runtime.

HTTPS is handled by Coolify's proxy. Do not configure TLS certificates, real domains, or IP addresses inside the application container.

Required Coolify environment variable names, using placeholders only:

```dotenv
APP_NAME=Rentier
APP_ENV=production
APP_KEY=base64:GENERATED_WITH_php_artisan_key_generate_show
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=Europe/Bucharest
APP_LOCALE=ro
APP_FALLBACK_LOCALE=en

RENTIER_REGISTRATION_ENABLED=false

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=<COOLIFY_POSTGRES_INTERNAL_HOST>
DB_PORT=5432
DB_DATABASE=<COOLIFY_POSTGRES_DATABASE>
DB_USERNAME=<COOLIFY_POSTGRES_USERNAME>
DB_PASSWORD=<COOLIFY_POSTGRES_PASSWORD>
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

PASSKEYS_USER_HANDLE_SECRET=GENERATED_RANDOM_SECRET
VITE_APP_NAME="${APP_NAME}"
```

Prefer the separate `DB_*` variables above for Coolify because they are explicit and match Laravel's config names. Remove `DB_URL` from the Coolify application environment when switching to this style so there is one database configuration source. This repository also supports Coolify's PostgreSQL URL as `DB_URL` for the `pgsql` connection because `config/database.php` maps `connections.pgsql.url` to `env('DB_URL')`; still set `DB_CONNECTION=pgsql`. The current config does not read `DATABASE_URL`.

Use this Coolify post-deployment command:

```bash
php artisan migrate --force &&
php artisan storage:link --force &&
php artisan optimize:clear &&
php artisan optimize
```

Do not run migrations during the Docker image build. Never run `migrate:fresh`, `db:wipe`, or destructive reset commands on beta or production. `queue:restart` is not required in the post-deployment command because Coolify recreates the application container and Supervisor starts a fresh worker.

Application logs are visible in Coolify's application logs. The container sends Nginx, PHP-FPM, queue worker, scheduler, and Supervisor logs to stdout/stderr where practical.

Run Playwright against the deployed URL with test-only beta credentials:

```bash
E2E_BASE_URL=https://your-domain.example E2E_EMAIL=beta-test-user@example.com E2E_PASSWORD=TEST_PASSWORD npm run test:e2e
```

Do not commit E2E credentials.

## Production Env Checklist

Create the production `.env` on the server only. Do not commit it.

```dotenv
APP_NAME=Rentier
APP_ENV=production
APP_KEY=base64:GENERATED_WITH_php_artisan_key_generate_show
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

PASSKEYS_USER_HANDLE_SECRET=GENERATED_RANDOM_SECRET
VITE_APP_NAME="${APP_NAME}"
```

Generate `APP_KEY` with:

```bash
php artisan key:generate --show
```

Generate `PASSKEYS_USER_HANDLE_SECRET` separately and store it only in the production environment. If it is omitted, the app can fall back to `APP_KEY`, but a dedicated secret is cleaner for beta.

## Database

Use PostgreSQL for beta production. Create a database and least-privilege application user using the placeholder names from the `.env` checklist.

Run deploy migrations with:

```bash
php artisan migrate --force
```

Never use `migrate:fresh` on beta or production. Do not run local seeders unless intentionally creating test data.

## Queue

The app currently sends team invitation notifications through a queued notification. Use the database queue for the first single-server beta:

```dotenv
QUEUE_CONNECTION=database
```

The repository includes the `jobs`, `job_batches`, and `failed_jobs` migrations. Use `deploy/systemd/rentier-queue.service.example` as the systemd template.

The deploy script restarts the queue service with `sudo -n systemctl restart rentier-queue` so unattended deploys fail clearly instead of prompting for a password. Allow `DEPLOY_USER` to restart only the Rentier queue service, not unrestricted passwordless sudo.

Install a reviewed sudoers file with `visudo`, for example at `/etc/sudoers.d/rentier-deploy`:

```sudoers
DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl restart rentier-queue
```

Root or sudo is also required to install OS packages, create system users, write Nginx and systemd configuration, reload Nginx/PHP-FPM, install cron entries under `/etc/cron.d`, create backup directories under `/var/backups`, and configure TLS certificates.

## Scheduler

The app has one scheduled task:

- Daily deletion of expired team invitations

Install one cron entry for the Laravel scheduler using `deploy/cron/rentier-scheduler.example`. The template uses non-blocking `flock` with `/tmp/rentier-scheduler.lock` and writes scheduler output to `/var/www/YOUR_APP_PATH/current/storage/logs/scheduler.log`.

Ensure `storage/logs` is writable by `DEPLOY_USER` and by the PHP-FPM/web-server user as appropriate for the server ownership model.

## HTTPS and Nginx

Use `deploy/nginx/rentier.conf.example` as the starting point. It contains an HTTP port 80 redirect to HTTPS and an HTTPS port 443 Laravel server block with placeholder certificate paths. Certbot may create or manage the HTTPS directives instead.

Replace:

- `YOUR_DOMAIN`
- `/var/www/YOUR_APP_PATH/current`
- `/etc/ssl/YOUR_DOMAIN/fullchain.pem`
- `/etc/ssl/YOUR_DOMAIN/privkey.pem`
- `/run/php/php8.X-fpm.sock`

The PHP-FPM socket must match the installed PHP 8.4+ version, for example `/run/php/php8.4-fpm.sock` or a newer installed socket.

## Health Check

Laravel health routing is already enabled at:

```text
/up
```

Use it for basic load balancer, uptime, or manual checks. It should not expose sensitive configuration.

## Deployment Templates

Review and adapt these templates:

- `deploy/nginx/rentier.conf.example`
- `deploy/systemd/rentier-queue.service.example`
- `deploy/cron/rentier-scheduler.example`
- `deploy/scripts/deploy.sh.example`
- `deploy/scripts/backup-postgresql.sh.example`

The deploy script uses `git pull --ff-only`, `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`, `php artisan migrate --force`, Laravel optimization commands, and a queue restart. It does not reset the database.

## Backups

Create timestamped PostgreSQL dumps before risky deploys and on an automated schedule. Store backup files outside the web root and copy them to durable off-server storage.

Keep database credentials out of backup scripts. For non-interactive `pg_dump`, prefer `PGPASSFILE` or a PostgreSQL `.pgpass` file owned by the backup-running user.

The passfile format is:

```text
hostname:port:database:username:password
```

Permissions must be `0600`, and the file must be owned/readable by the user running the backup. Peer authentication for a carefully configured local PostgreSQL backup user is also acceptable.

Backup retention is an operator decision. The provided template does not delete old backups automatically.

Test restores into a separate database before inviting beta users.

## Post-Deploy Smoke Tests

Manual checks:

1. Visit `https://your-domain.example/up`.
2. Visit `/` and confirm built assets load without the Vite dev server.
3. Confirm `/register` is unavailable when `RENTIER_REGISTRATION_ENABLED=false`.
4. Confirm email verification, password reset, and team invitation mail delivery.
5. Confirm login redirects to the current team dashboard.
6. Create and clean up a test property, lease, rent payment, and expense.
7. Check queue worker, scheduler, Nginx, PHP-FPM, and Laravel logs.

Existing Playwright smoke tests can run against the deployed beta with test-only credentials:

```bash
E2E_BASE_URL=https://your-domain.example E2E_EMAIL=beta-test-user@example.com E2E_PASSWORD=TEST_PASSWORD npm run test:e2e
```

Do not commit E2E credentials.

## VPS Tasks Still Required

- Point DNS for `your-domain.example` to the Hetzner server IP.
- Create the deploy user and SSH access.
- Install Ubuntu security updates and configure the firewall.
- Install Nginx, PHP, Composer, Node/npm, and PostgreSQL.
- Create the PostgreSQL database and application user.
- Create the server-side `.env` with real production values.
- Configure HTTPS certificates and replace the Nginx certificate and PHP-FPM socket placeholders.
- Configure `DEPLOY_USER` sudoers access for only restarting the Rentier queue service.
- Ensure `storage/`, `storage/logs/`, and `bootstrap/cache/` are writable by the correct server users.
- Configure `PGPASSFILE`, `.pgpass`, or peer authentication for backups.
- Install the Nginx, systemd, cron, deploy, and backup templates after replacing placeholders.
- Run the first deploy and verify `/up`, app auth, mail, queue, scheduler, and backups.
