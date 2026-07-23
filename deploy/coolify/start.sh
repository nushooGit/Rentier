#!/usr/bin/env bash
set -euo pipefail

log() {
    printf '[rentier-start] %s\n' "$*"
}

fail() {
    log "ERROR: $*"
    exit 1
}

resolve_command() {
    local label="$1"
    shift
    local path
    local candidate

    for candidate in "$@"; do
        path="$(command -v "$candidate" 2>/dev/null || true)"
        if [ -n "$path" ]; then
            printf '%s\n' "$path"
            return 0
        fi
    done

    return 1
}

require_file() {
    local path="$1"

    if [ ! -r "$path" ]; then
        fail "required file is missing or unreadable: $path"
    fi

    log "file ok: $path"
}

export PORT="${PORT:-80}"

log "starting Rentier container"
log "user: $(id)"
log "working directory: $(pwd)"
log "PORT: ${PORT}"

BASH_BIN="$(resolve_command bash bash)" || fail "required command not found: bash"
SUPERVISORD_BIN="$(resolve_command supervisord supervisord)" || fail "required command not found: supervisord"
NGINX_BIN="$(resolve_command nginx nginx)" || fail "required command not found: nginx"
PHP_BIN="$(resolve_command php php)" || fail "required command not found: php"
PHP_FPM_BIN="$(resolve_command php-fpm php-fpm php-fpm8.4 php-fpm84)" || fail "required command not found: php-fpm"
NODE_BIN="$(resolve_command node node)" || fail "required command not found: node"
export BASH_BIN SUPERVISORD_BIN NGINX_BIN PHP_BIN PHP_FPM_BIN NODE_BIN

log "command bash: $BASH_BIN"
log "command supervisord: $SUPERVISORD_BIN"
log "command nginx: $NGINX_BIN"
log "command php: $PHP_BIN"
log "command php-fpm: $PHP_FPM_BIN"
log "command node: $NODE_BIN"

id www-data >/dev/null 2>&1 || fail "required runtime user does not exist: www-data"
log "runtime user www-data: $(id www-data)"

require_file /app/artisan
require_file /app/deploy/coolify/nginx.template.conf
require_file /app/deploy/coolify/php-fpm.conf
require_file /app/deploy/coolify/supervisord.conf
require_file /app/deploy/coolify/worker-nginx.conf
require_file /app/deploy/coolify/worker-php-fpm.conf
require_file /app/deploy/coolify/worker-queue.conf
require_file /app/deploy/coolify/worker-scheduler.conf
require_file /assets/scripts/prestart.mjs

log "php version:"
"$PHP_BIN" -v || fail "php -v failed"

log "required PHP database modules:"
"$PHP_BIN" -m | grep -Fx PDO || fail "required PHP module not loaded: PDO"
"$PHP_BIN" -m | grep -Fx pdo_pgsql || fail "required PHP module not loaded: pdo_pgsql"

mkdir -p /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R ug+rwX /app/storage /app/bootstrap/cache

"$PHP_BIN" /app/artisan storage:link --force || true

mkdir -p /tmp
"$NODE_BIN" /assets/scripts/prestart.mjs /app/deploy/coolify/nginx.template.conf /etc/nginx.conf

log "testing nginx configuration"
"$NGINX_BIN" -t -e stderr -c /etc/nginx.conf || fail "nginx configuration test failed"

log "testing php-fpm configuration"
"$PHP_FPM_BIN" -t -y /app/deploy/coolify/php-fpm.conf || fail "php-fpm configuration test failed"

log "testing supervisor configuration"
if "$SUPERVISORD_BIN" --help 2>&1 | grep -q -- ' -t'; then
    "$SUPERVISORD_BIN" -t -c /app/deploy/coolify/supervisord.conf || fail "supervisor configuration test failed"
else
    log "supervisord does not expose a config-test flag; skipping static supervisor test"
fi

log "starting supervisor"
exec "$SUPERVISORD_BIN" -c /app/deploy/coolify/supervisord.conf -n
