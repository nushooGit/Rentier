# DOMAIN-01: app.rentier.ro preflight and safe rollout

Status: preparation only. No DNS, Coolify, production credentials, databases, or live routing changed by this document.

## Target hosts

- `app.rentier.ro`: the existing Laravel/Inertia landlord application, with Fortify login and password reset.
- `rentier.ro`: public landing page only **after** the app subdomain has passed production smoke checks.
- `admin.rentier.ro`: separate future internal administration host; **not** part of DOMAIN-01. Maintain separate platform-admin authorization and host-only sessions.

Keep one Laravel backend authentication system. Do not broaden session cookies to `.rentier.ro` for convenience.

## Preflight (read-only)

1. Confirm the current production commit and GitHub CI. Record current Coolify deployment, app domain settings and whether merges into `main` trigger automatic deployments.
2. Verify a recent **off-VPS** PostgreSQL backup and successful independent restore before any domain cutover. Do not run migrations merely to change the domain.
3. Read current Cloudflare records, Coolify hostname/proxy/certificate configuration, HTTPS redirects and application configuration **without copying or publishing credentials**.
4. Audit all absolute URLs, callback origins and signed routes. Password-reset links currently use `APP_URL`; Fortify passkeys use `APP_URL` as relying-party/allowed origin. Review signed invitations, verification links, authentication redirects and passkey enrollment before switching the canonical URL.
5. Review all background workers and scheduler settings. Do not start a second live instance pointing at the same production database with duplicate workers or schedulers.

## Implementation in a separate reviewable PR

- Introduce only the minimal application changes required for the new canonical host, with tests for URL generation, password-reset/verification links and auth redirects as appropriate.
- Keep `SESSION_DOMAIN` unset/null (host-only), `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, HTTPS and appropriate SameSite behavior. Users may need to log in separately after changing hosts; no cross-subdomain SSO is planned.
- Update deployment documentation to show `APP_URL=https://app.rentier.ro`. Keep the existing verified SMTP sending domain and sender configuration unchanged.
- Include a rollback procedure and an acceptance checklist. Do not combine this with building the public landing page or internal admin.

## Controlled release (requires explicit infrastructure-change approval)

1. Set up `app.rentier.ro` DNS, HTTPS and routing in Cloudflare/Coolify. Prefer routing the new host to the *same* healthy application during cutover rather than launching duplicate production workers. Preserve the current `rentier.ro` app until new-host smoke checks pass.
2. At the scheduled cutover, update Coolify's canonical `APP_URL`, review any proxy/redirect/cached configuration and verify the effective app URL. Do not expose secrets in logs or screenshots.
3. Smoke-test `/up`, built assets, login/logout, host-only cookie attributes, Fortify login throttling, password reset, email verification/invitations, authorization boundaries and passkeys (only if already used). Confirm fresh generated email links point to the new host and work.
4. Confirm queue/scheduler behavior and check Coolify, proxy and Laravel logs. Verify normal landlord workflow navigation, mobile screens and direct/bookmarked links.
5. Only after `app.rentier.ro` passes, plan a separate WEB-01 landing deployment on `rentier.ro`. Provide a deliberate redirect strategy for old app links; avoid redirect loops and avoid serving both an active app and public landing from the same apex route.
6. On any failed auth, signed-link, email, SSL, proxy or worker check: keep/revert the apex application, restore the previous canonical `APP_URL`/routing, and rerun smoke tests. Do not roll back or reset production data.

## Current gate

This document records **no** actual DNS change, server change, backup result or production subdomain verification. The exact Coolify and Cloudflare steps must be tailored to a read-only configuration inspection when the infrastructure change is approved.
