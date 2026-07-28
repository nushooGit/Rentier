$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $root

if (-not (Test-Path '.env.e2e')) {
    Copy-Item '.env.e2e.example' '.env.e2e'
}

$env:APP_ENV = 'e2e'
$env:APP_URL = 'http://127.0.0.1:8010'
$env:E2E_BASE_URL = 'http://127.0.0.1:8010'
$env:E2E_EMAIL = if ($env:E2E_EMAIL) { $env:E2E_EMAIL } else { 'e2e@rentier.test' }
$env:E2E_PASSWORD = if ($env:E2E_PASSWORD) { $env:E2E_PASSWORD } else { 'password' }

php artisan e2e:bootstrap --env=e2e

npx concurrently -c "#93c5fd,#c4b5fd" "php artisan serve --env=e2e --host=127.0.0.1 --port=8010" "npm run dev -- --host 127.0.0.1 --port 5174"
