#!/usr/bin/env bash
# Run only inside a disposable Codex Cloud container, never on a server.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/guard.sh"

for tool in php composer node npm; do
    command -v "$tool" >/dev/null || fail "Missing $tool; see docs/codex-cloud.md."
done
php -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);' || fail 'PHP >= 8.4.1 is required.'
[[ "$(composer --version --no-ansi)" == 'Composer version 2.'* ]] || fail 'Composer 2 is required.'
node -e 'const [major, minor] = process.versions.node.split(".").map(Number); process.exit(major === 22 && minor >= 12 ? 0 : 1)' || fail 'Node 22.12+ (major 22) is required by locked Vite.'
php -r '
$sqliteOnly = getenv("RENTIER_CODEX_SQLITE_ONLY") === "1";
$required = ["pdo_sqlite", "pdo_pgsql", "bcmath", "curl", "zip"];
$lock = json_decode(file_get_contents("composer.lock"), true, flags: JSON_THROW_ON_ERROR);
$root = json_decode(file_get_contents("composer.json"), true, flags: JSON_THROW_ON_ERROR);
foreach (array_merge([$root], $lock["packages"], $lock["packages-dev"]) as $package) {
    foreach (array_keys($package["require"] ?? []) as $requirement) {
        if (str_starts_with($requirement, "ext-")) {
            $required[] = substr($requirement, 4);
        }
    }
}
$missing = array_filter(array_unique($required), fn ($extension) => !($sqliteOnly && $extension === "pdo_pgsql") && !extension_loaded($extension));
if ($missing) {
    fwrite(STDERR, "Missing PHP extensions: ".implode(", ", $missing).PHP_EOL);
    exit(1);
}
'

umask 077
if [[ -e .env ]]; then
    check_managed_env
else
    mkdir -p .codex-local
    database_dir=$(mktemp -d /tmp/rentier-codex.XXXXXX)
    database="$database_dir/database.sqlite"
    touch "$database"
    # Start from the project's example; replace only local runtime settings.
    sed -E '/^(APP_KEY|APP_ENV|APP_URL|DB_CONNECTION|DB_DATABASE|DB_URL|SESSION_DRIVER|CACHE_STORE|QUEUE_CONNECTION|MAIL_MAILER|RENTIER_AUTO_MIGRATE)=/d' .env.example > .env
    {
        printf '\nAPP_ENV=local\nAPP_URL=http://localhost\n'
        printf 'APP_KEY=base64:%s\n' "$(php -r 'echo base64_encode(random_bytes(32));')"
        printf 'DB_CONNECTION=sqlite\nDB_DATABASE=%s\nDB_URL=\n' "$database"
        printf 'SESSION_DRIVER=file\nCACHE_STORE=file\nQUEUE_CONNECTION=sync\nMAIL_MAILER=log\nRENTIER_AUTO_MIGRATE=false\n'
    } >> .env
    printf '%s\n' "$database" > .codex-local/database-path
    sha256sum .env > .codex-local/env.sha256
fi

php -v
composer --version
node --version
npm --version
# Keep both lock files unchanged and include development dependencies.
if [[ "${RENTIER_CODEX_SQLITE_ONLY:-}" == 1 ]]; then
    composer install --no-interaction --prefer-dist --ignore-platform-req=ext-pdo_pgsql
    # check-platform-reqs has no selective ignore flag. Inspect its complete
    # machine-readable report and accept only the explicit PostgreSQL exception.
    platform_status=0
    composer check-platform-reqs --format=json > .codex-local/platform-requirements.json || platform_status=$?
    php -r '
$requirements = json_decode(file_get_contents($argv[1]), true);
if (!is_array($requirements)) {
    fwrite(STDERR, "Composer platform verification failed.\n");
    exit(1);
}
$seenPgsql = false;
$missingPgsql = false;
$failed = false;
foreach ($requirements as $requirement) {
    if (!isset($requirement["name"], $requirement["status"])) {
        fwrite(STDERR, "Invalid Composer platform report.\n");
        exit(1);
    }
    if ($requirement["name"] === "ext-pdo_pgsql") {
        $seenPgsql = true;
        if ($requirement["status"] !== "success") {
            $missingPgsql = true;
            fwrite(STDERR, "SQLite-only: ignoring missing ext-pdo_pgsql.\n");
        }
    } elseif ($requirement["status"] !== "success") {
        fwrite(STDERR, $requirement["name"]." failed: ".$requirement["status"]."\n");
        $failed = true;
    }
}
if (!$seenPgsql || $failed || ((int) $argv[2] !== 0 && !$missingPgsql)) {
    exit(1);
}
' .codex-local/platform-requirements.json "$platform_status" || {
        printf 'Composer check-platform-reqs exited %s; complete report:\n' "$platform_status" >&2
        cat .codex-local/platform-requirements.json >&2
        exit 1
    }
else
    composer install --no-interaction --prefer-dist
    composer check-platform-reqs
fi
npm ci --no-audit --no-fund
# Only the generated /tmp SQLite database is used. No reset or seed operation.
check_managed_env
php artisan migrate --database=sqlite --no-interaction
# Generates Wayfinder types and Vite manifest needed by checks and feature tests.
npm run build
printf 'Setup complete. Run bash scripts/codex/validate.sh for the full validation.\n'
