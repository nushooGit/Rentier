#!/usr/bin/env bash
set -euo pipefail

export PORT="${PORT:-80}"

mkdir -p /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R ug+rwX /app/storage /app/bootstrap/cache

php /app/artisan storage:link --force || true

node /assets/scripts/prestart.mjs /app/deploy/coolify/nginx.template.conf /etc/nginx.conf

exec supervisord -c /etc/supervisord.conf -n
