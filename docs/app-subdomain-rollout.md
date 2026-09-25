# DOMAIN-01: app.rentier.ro preflight and safe rollout

Status: preparation and dual-host routing verified. The Cloudflare `app.rentier.ro` DNS record and Coolify's second hostname are configured. No canonical `APP_URL` cutover, production database change, secret change or migration has been performed.

## Target hosts

- `app.rentier.ro`: the existing Laravel/Inertia landlord application, with Fortify login and password reset.
- `rentier.ro`: public landing page only **after** the app subdomain has passed production smoke checks.
- `admin.rentier.ro`: separate future internal administration host; **not** part of DOMAIN-01. Maintain separate platform-admin authorization and host-only sessions.

Keep one Laravel backend authentication system. Do not broaden session cookies to `.rentier.ro` for convenience.

## Preflight (read-only)

1. Confirm the current production commit and GitHub CI. Record current Coolify deployment, app domain settings and whether merges into `main` trigger automatic deployments.
2. Verify a recent **off-VPS** PostgreSQL backup and successful independent restore before any domain cutover. Do not run migrations merely to change the domain.
3. Read current Cloudflare records, Coolify hostname/proxy/certificate configuration, HTTPS redirects and application configuration **without copying or publishing credentials**.
4. Audit all absolute URLs, callback origins and signed routes. Password reset uses `APP_URL`; Laravel email-verification signed links and invitation links are host-dependent. Existing email-verification links signed for the old host may fail on the new host: leave `rentier.ro` serving the existing app through their expiry window and issue fresh links from the new host. Review authentication redirects and passkey enrollment before switching the canonical URL. Fortify passkeys now support `PASSKEYS_RELYING_PARTY_ID` to keep the existing RP ID (`rentier.ro`) while `APP_URL` and allowed origins move to `https://app.rentier.ro`. Confirm the RP ID actually used by currently enrolled credentials before applying any override.
5. Review all background workers and scheduler settings. Do not start a second live instance pointing at the same production database with duplicate workers or schedulers.

## Implementation in a separate reviewable PR

- Introduce only the minimal application changes required for the new canonical host, with tests for reset/verification/invitation links, host-only sessions, and passkey RP continuity. The optional `PASSKEYS_RELYING_PARTY_ID` falls back to the `APP_URL` hostname when unset; a deliberate value of `rentier.ro` retains the existing parent-domain WebAuthn RP ID across the subdomain cutover.
- Keep `SESSION_DOMAIN` unset/null (host-only), `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, HTTPS and appropriate SameSite behavior. Users may need to log in separately after changing hosts; no cross-subdomain SSO is planned.
- Update deployment documentation to show `APP_URL=https://app.rentier.ro` and the *optional, approved* `PASSKEYS_RELYING_PARTY_ID=rentier.ro`. Keep the existing verified SMTP sending domain and sender configuration unchanged. Never change `PASSKEYS_USER_HANDLE_SECRET` during the cutover.
- Include a rollback procedure and an acceptance checklist. Do not combine this with building the public landing page or internal admin.

## Controlled release (requires explicit infrastructure-change approval)

1. Set up `app.rentier.ro` DNS, HTTPS and routing in Cloudflare/Coolify. Prefer routing the new host to the *same* healthy application during cutover rather than launching duplicate production workers. Preserve the current `rentier.ro` app until new-host smoke checks pass.
2. At the scheduled cutover, update Coolify's canonical `APP_URL`, preserve an approved passkey RP ID if existing credentials require it, review proxy/redirect/cached configuration and verify the effective app URL. Keep existing signing keys and `PASSKEYS_USER_HANDLE_SECRET`; do not expose secrets in logs or screenshots.
3. Smoke-test `/up`, built assets, login/logout, host-only cookie attributes, Fortify login throttling, password reset, email verification/invitations, authorization boundaries and passkeys (only if already used). Confirm fresh generated email links point to the new host and work. Test any previously enrolled passkey on the new host before retiring the old host; HTTPS origin must match `allowed_origins`.
4. Confirm queue/scheduler behavior and check Coolify, proxy and Laravel logs. Verify normal landlord workflow navigation, mobile screens and direct/bookmarked links.
5. Only after `app.rentier.ro` passes, plan a separate WEB-01 landing deployment on `rentier.ro`. Provide a deliberate redirect strategy for old app links; avoid redirect loops and avoid serving both an active app and public landing from the same apex route.
6. On any failed auth, signed-link, email, SSL, proxy or worker check: keep/revert the apex application, restore the previous canonical `APP_URL`/routing, and rerun smoke tests. Do not roll back or reset production data.

## Verified progress and remaining release gates (2026-09-25)

- Owner-configured Cloudflare DNS `A app.rentier.ro → 78.47.50.142`, DNS-only, TTL Auto. The apex `rentier.ro` stays on the same IP.
- Both hostnames were added to the **same** Coolify application (not a duplicate application); owner confirmed successful HTTPS `/up` on `rentier.ro` and `app.rentier.ro`. The canonical `APP_URL` has **not** been switched or independently verified on the new host.
- Off-VPS backup copied to Windows from the 2026-09-24 Coolify run: PostgreSQL 17.10 custom-format archive, 159 TOC entries. Owner restored it into the isolated local PostgreSQL 17.11 database `rentier_restore_test` using `pg_restore --exit-on-error` (exit code 0), then confirmed 2 users, 2 properties, 1 lease, 6 rent payments and 4 expenses.
- The **restored snapshot** contains **0 passkeys** as confirmed by a local read-only SQL query. This is **not** a live database check: confirm no passkeys were enrolled after the backup if preserving an existing RP ID would matter. Do not set `PASSKEYS_RELYING_PARTY_ID` on the strength of this historical snapshot alone.
- PR #8 CI passed for PHP 8.4, PHP 8.5, PostgreSQL, linter and Codex environment on commit `fe9a2f7`; recheck the latest head after any documentation changes.
- **Before merging**: establish whether Coolify automatically deploys `main`; merging PR #8 can rebuild the live application. Keep the current deployed application available and do not combine the merge with the canonical URL cutover.
- **Before changing `APP_URL`**: confirm PR #8 deployment, check `RENTIER_AUTO_MIGRATE=false` and host-only sessions, take and download a **fresh** backup, verify current mail/reset-link URL handling, and retain the apex route and rollback configuration.
- DNS and Coolify dual-host routing are complete, but production login, password reset, verification/invitations and host-only session checks on `app.rentier.ro` remain pending.

## Repository changes prepared for review

- Fortify's RP ID now has a backward-compatible optional `PASSKEYS_RELYING_PARTY_ID` override; the unset default still derives from `APP_URL`. This is **not** a production configuration change.
- Focused tests simulate the app host and assert reset, invitation and signed verification link generation, and the explicitly configured parent-domain passkey RP ID.
- Old signed verification links and browser host-only sessions do not automatically migrate; plan a transition period and fresh login on the new host. A passkey RP ID is not the same thing as a session-cookie domain.
