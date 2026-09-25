# DOMAIN-01: app.rentier.ro preflight and safe rollout

Status: VERIFIED PRODUCTION on 2026-09-25. Canonical `APP_URL=https://app.rentier.ro` is live. Both hosts still route to the same Coolify application until WEB-01 has a tested public-host and legacy-link transition. No domain-cutover database migrations or authentication-key changes were performed.

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

## Final production acceptance and remaining transitions (2026-09-25)

- GitHub PR #8 was merged as `2d5201c` and manually deployed. The owner configured Cloudflare DNS-only routing for `app.rentier.ro` and kept both HTTPS hostnames on the **same** Coolify application. The fixed apex `ASSET_URL` was removed after cross-origin asset failures; same-origin assets now work on both hosts.
- An earlier PostgreSQL custom-format backup was successfully restored on the owner's isolated local PostgreSQL instance with read-only row-count checks. The owner downloaded a fresh off-VPS backup immediately before changing the canonical URL.
- Canonical `APP_URL=https://app.rentier.ro` was deployed. The owner verified both hosts' `/up` endpoints, successful login and logout on the new host, dashboard/properties navigation, delivery of a real password-reset email containing a new-host URL, and a host-only `rentier-session` cookie with `Secure`, `HttpOnly` and `SameSite=Lax`.
- Supervisor reported Nginx, PHP-FPM, queue and scheduler RUNNING after the domain cutover. The later SEC-LOG-01 release (`566e04a`) was successfully deployed on 2026-09-25; a synthetic URL probe confirmed Nginx logs show `[path-redacted]` and a redacted Referer. See issue #9 and PRs #10/#11. Existing earlier log files remain sensitive.
- The initial SEC-LOG-01 Docker build failed with Composer exit 255. A controlled retry later succeeded, and GitHub CI's isolated no-dev Composer check passed. The cause of the original failure remains **unconfirmed**; do not report a confirmed OOM or permanent fix.
- Still pending before the **separate** WEB-01 public-site release: preserve or deliberately transition old apex-host signed verification/invitation links, direct application bookmarks and login traffic; do not redirect all requests blindly. Review live passkey enrollment if passkeys are in use (the older restored snapshot had zero; live count has not been checked). Keep host-only cookies, one queue/scheduler instance and existing SMTP/authentication secrets.
- **Next task:** build and test a Romanian-first responsive public landing page in a separate branch/PR. Keep the existing apex application routed until the new public-host routing and legacy-link transition have been independently tested. Do not deploy or modify Cloudflare/Coolify for WEB-01 without explicit approval.

## Repository changes prepared for review

- Fortify's RP ID now has a backward-compatible optional `PASSKEYS_RELYING_PARTY_ID` override; the unset default still derives from `APP_URL`. This is **not** a production configuration change.
- Focused tests simulate the app host and assert reset, invitation and signed verification link generation, and the explicitly configured parent-domain passkey RP ID.
- Old signed verification links and browser host-only sessions do not automatically migrate; plan a transition period and fresh login on the new host. A passkey RP ID is not the same thing as a session-cookie domain.
