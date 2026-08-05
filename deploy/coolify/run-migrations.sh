#!/usr/bin/env bash
set -euo pipefail

log() {
    printf '[rentier-migrate] %s\n' "$*"
}

fail() {
    log "ERROR: $*"
    exit 1
}

auto_migrate="${RENTIER_AUTO_MIGRATE:-false}"
app_env="${APP_ENV:-}"

if [ "$auto_migrate" != "true" ]; then
    log "automatic migrations are disabled; set RENTIER_AUTO_MIGRATE=true to enable"
    exit 0
fi

if [ "$app_env" != "production" ]; then
    log "automatic migrations require APP_ENV=production; current APP_ENV is '${app_env:-unset}'"
    exit 0
fi

PHP_BIN="${PHP_BIN:-php}"
ARTISAN="${ARTISAN:-/app/artisan}"
APP_DIR="${APP_DIR:-/app}"
MIGRATION_WAIT_TIMEOUT="${RENTIER_MIGRATION_WAIT_TIMEOUT:-60}"
MIGRATION_WAIT_INTERVAL="${RENTIER_MIGRATION_WAIT_INTERVAL:-3}"

case "$MIGRATION_WAIT_TIMEOUT" in
    ''|*[!0-9]*) fail "RENTIER_MIGRATION_WAIT_TIMEOUT must be a positive integer" ;;
esac

case "$MIGRATION_WAIT_INTERVAL" in
    ''|*[!0-9]*) fail "RENTIER_MIGRATION_WAIT_INTERVAL must be a positive integer" ;;
esac

if [ "$MIGRATION_WAIT_TIMEOUT" -le 0 ]; then
    fail "RENTIER_MIGRATION_WAIT_TIMEOUT must be greater than zero"
fi

if [ "$MIGRATION_WAIT_INTERVAL" -le 0 ]; then
    fail "RENTIER_MIGRATION_WAIT_INTERVAL must be greater than zero"
fi

if [ ! -r "$ARTISAN" ]; then
    fail "artisan is missing or unreadable"
fi

cd "$APP_DIR"

db_ready() {
    "$PHP_BIN" -r '
        require getcwd()."/vendor/autoload.php";
        $app = require getcwd()."/bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->make("db")->connection()->getPdo()->query("select 1");
    ' >/dev/null 2>&1
}

log "waiting up to ${MIGRATION_WAIT_TIMEOUT}s for the configured database connection"
elapsed=0
until db_ready; do
    if [ "$elapsed" -ge "$MIGRATION_WAIT_TIMEOUT" ]; then
        fail "database was not reachable before timeout"
    fi

    log "database is not reachable yet; retrying in ${MIGRATION_WAIT_INTERVAL}s"
    sleep "$MIGRATION_WAIT_INTERVAL"
    elapsed=$((elapsed + MIGRATION_WAIT_INTERVAL))
done
log "database connection is reachable"

migrate_args=(migrate --force --no-interaction)
cache_store="${CACHE_STORE:-database}"

if "$PHP_BIN" "$ARTISAN" migrate --help 2>/dev/null | grep -q -- '--isolated'; then
    case "$cache_store" in
        database|redis|memcached|dynamodb)
            migrate_args+=(--isolated)
            log "using Laravel migration isolation with shared CACHE_STORE=${cache_store}"
            ;;
        *)
            log "Laravel migration isolation is supported but CACHE_STORE=${cache_store} is not a shared lock backend"
            ;;
    esac
else
    log "Laravel migrate command does not advertise --isolated; continuing without migration isolation"
fi

log "running php artisan ${migrate_args[*]}"
"$PHP_BIN" "$ARTISAN" "${migrate_args[@]}" || fail "database migration failed"
log "database migrations completed"
