#!/usr/bin/env bash
# Shared by setup and validation; never source the application's .env.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

fail() { printf 'Codex environment: %s\n' "$*" >&2; exit 1; }

# Process variables override Laravel's .env and PHPUnit's defaults.
while IFS='=' read -r name _; do
    case "$name" in
        APP_*|DB_*|DATABASE_URL|SESSION_*|CACHE_*|QUEUE_*|MAIL_*|REDIS_*|AWS_*|FILESYSTEM_*|RENTIER_*|BROADCAST_*|E2E_*)
            fail "Remove inherited $name from environment settings; use the generated local .env." ;;
    esac
done < <(env)
[[ ! -e bootstrap/cache/config.php ]] || fail 'Cached Laravel configuration exists; use a clean disposable checkout.'
[[ ! -L .env && ! -L .codex-local ]] || fail 'Symlinked environment files are not supported.'

check_managed_env() {
    [[ -f .codex-local/env.sha256 && -f .codex-local/database-path ]] || fail 'Existing .env is unmanaged; use a fresh checkout.'
    sha256sum --check --status .codex-local/env.sha256 || fail 'Managed .env changed; use a fresh checkout.'
    local database
    database=$(cat .codex-local/database-path)
    [[ "$database" == /tmp/rentier-codex.*/database.sqlite && -f "$database" && ! -L "$database" ]] || fail 'Disposable SQLite database is missing or invalid; use a fresh checkout.'
}
