#!/usr/bin/env bash
# Start the existing isolated E2E app on Linux/macOS. Never use a remote database.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

if [[ -n "${DB_URL:-}" || -n "${DATABASE_URL:-}" ]]; then
    echo "Refusing isolated E2E bootstrap with an inherited database URL." >&2
    exit 1
fi

if [[ ! -f .env.e2e ]]; then
    cp .env.e2e.example .env.e2e
fi

export APP_ENV=e2e
export APP_URL=http://127.0.0.1:8010
export E2E_BASE_URL=http://127.0.0.1:8010
export E2E_EMAIL="${E2E_EMAIL:-e2e@rentier.test}"
export E2E_PASSWORD="${E2E_PASSWORD:-password}"

# e2e:bootstrap verifies APP_ENV, SQLite, the exact dedicated DB path,
# symlink safety and the local URL before its guarded database reset.
php artisan e2e:bootstrap --env=e2e

exec php artisan serve --env=e2e --host=127.0.0.1 --port=8010
