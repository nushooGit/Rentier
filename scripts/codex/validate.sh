#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/guard.sh"
check_managed_env
mkdir -p .codex-local/validation
results=.codex-local/validation/results.tsv
printf 'command\texit_code\n' > "$results"
failed=0
run_check() {
    local name="$1" status
    shift
    printf '\nRunning: %s\n' "$*"
    set +e
    "$@" 2>&1 | tee ".codex-local/validation/$name.log"
    status=${PIPESTATUS[0]}
    set -e
    printf '%s\t%s\n' "$*" "$status" >> "$results"
    if [[ "$status" -ne 0 ]]; then failed=1; fi
}
run_check php-version php -v
run_check composer-version composer --version
run_check node-version node --version
run_check npm-version npm --version
# Dependency installation is done once by setup.sh, not repeated here.
run_check tests php artisan test
run_check php-types composer run types:check
run_check frontend-types npm run types:check
run_check lint npm run lint:check
run_check format npm run format:check
run_check build npm run build
run_check php-format composer run lint:check
cat "$results"
exit "$failed"
