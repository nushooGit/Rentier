# Renter Portal — Identity and Access Design

Status: proposal only, 2026-10-09. No authentication or schema changes authorized by this document.

## Repository facts

- Rentier uses one Laravel authentication backend and one `users` table.
- `renters` already includes nullable `user_id` with a foreign key to `users.id` (null on user deletion); `App\Models\Renter::user()` exists.
- Each `Renter` belongs to a workspace; `Lease` references a `Renter` and a workspace.
- `TeamInvitation` represents invitations for workspace membership and role assignment. **Do not reuse it to enroll renters**: being a renter must not imply landlord workspace membership.
- No safe renter-claim flow, portal visibility rules, or renter-specific authorization tests have been verified yet.

## Proposed security boundary

Authenticated `User` → only `Renter` records explicitly linked by `renters.user_id` → only leases attached to those records → only explicitly renter-visible related data. Never infer a link from equal email addresses, names, phone numbers or team membership.

One user may legitimately be a landlord in a workspace and a renter in another. Keep the current session and login system; do not introduce another users table, authentication backend or shared subdomain cookies. For every query, enforce access through policies and scoped relationships on the server, not through route guesses or hidden React controls. Tenant-facing copies should use the product's renter terminology; keep code identifiers in English.

## Proposed invitation/claim lifecycle (requires security review before coding)

1. Workspace owner selects an existing renter record and sends an invitation to the intended recipient. The invitation identifies the specific renter record and workspace; it does **not** invite the recipient to become a workspace member.
2. Create a one-time, high-entropy, expiring invitation token; store only a hash, scope it to `renter_id` and `team_id`, and record inviter, expiry, acceptance/revocation status. Never expose raw tokens in logs.
3. Recipient follows the invitation, authenticates with or registers a `User` account through the existing approved flows, and verifies control of the invited email before acceptance. Explicit acceptance is required; matching email alone never grants access.
4. Acceptance is atomic and rejects expired, revoked, already-used or mismatched tokens. It also rejects attempts to silently reassign a renter record linked to a different user. Support intentional revoke/relink via a separately authorized flow.
5. An invited person gains access only to their linked renter records and permitted resources, not to all resources in the landlord's workspace. The invite must not create `Membership` / `TeamInvitation`.
6. Existing records with a populated `renters.user_id` require an audit of how they were linked before exposing any renter-facing information; a non-null foreign key alone is insufficient proof of authorization.

## Implementation-readiness audit (2026-10-09)

Source files reviewed: `routes/web.php`, `LeaseController`, `Renter`, `LeasePolicy`, `TeamInvitationController`, `RespondToTeamInvitationRequest`, `CreateNewUser` and `config/fortify.php`.

- `LeaseController::store` creates a new workspace-scoped `Renter` contact, with **no** `user_id` assignment. `update` only updates contact fields. This confirms contact email is not an authenticated identity proof.
- Existing landlord pages live under `/{current_team}` and `EnsureTeamMembership`; renter routes must use a separate `auth` + `verified` + reject-admin-host surface, with dedicated scoped policies, not the current workspace-member route group.
- `LeasePolicy::view` currently checks workspace membership only; do **not** widen it globally to permit renters. Introduce a dedicated renter policy/view or explicitly contextual policy boundaries to avoid exposing landlord payloads.
- Existing `TeamInvitationController::accept` creates a `Membership` and switches workspace; it is categorically unsuitable for renter access. A new invitation type and lifecycle must not call it.
- Fortify supports email verification, but public registration depends on `RENTIER_REGISTRATION_ENABLED`. An invited person without an existing login cannot complete a claim while registration is disabled. The restricted enrollment path must be separately designed and security-reviewed, not enabled by turning public registration on.
- `CreateNewUser` also creates a personal workspace for every registered user; an eventual renter-only onboarding flow must either explicitly accept that behavior or safely adapt it after review.
- Review and constrain existing populated `renters.user_id` values before enabling any read endpoint: the schema allows assignments but the current lease controller does not establish a secure claim provenance.

### Decision gate for the backend PR

Before implementing an invitation-acceptance endpoint, approve a **renter-only, token-scoped enrollment path** when public registration is disabled, preserving Fortify validation/email verification and avoiding workspace membership grants. The next security design should specify one-time token hashing, signed/opaque link behavior, expiry, rate limits, atomic acceptance, audited revocation, and protection for existing links.

Until this gate is approved, only isolated non-auth tests, design and static review are in scope. No migration or credential-dependent testing is authorized. GitHub CI cannot substitute for production authorization verification.

## Minimum first implementation increment

Backend only: design/review token persistence needs (migration is likely for an invitation record, but `renters.user_id` itself already exists); add invitation issuing/revocation/acceptance flows with explicit ownership checks, email verification and tests. No production migration until independently reviewed and approved. No public registration configuration changes as part of this increment.

## Portal rollout (subsequent separate increments)

1. Read-only active and historical lease summaries, due dates and renter-specific payment history.
2. Renter-assigned utility bills and charges with careful separation of current charges and old balances.
3. Documents with an explicit `renter_visible` (or equivalent approved) publication gate; document existence and private download endpoints are also protected.
4. Calendar dates/reminders explicitly marked for renter visibility. Existing landlord-only reminders are private by default.
5. Mobile-first RO/EN interfaces and role-aware navigation on the existing application domain.

## Authorization and regression acceptance tests

- Unlinked account sees no renter data, even when its email equals `renters.email`.
- User with a valid claimed renter record sees only its own renter/lease/payment rows, not other renters from the same property/workspace.
- URL/ID guessing, direct downloads, CSV exports, or Inertia payloads never bypass renter policies.
- Landlord memberships grant no implicit renter identity, and renter identity grants no landlord workspace permissions.
- Invitation cannot be reused, replayed, redeemed after expiry or revoked, or consumed by an unverified recipient.
- A claim cannot overwrite another user's existing link or cross workspace boundaries.
- Historical and ended leases follow explicit visibility rules; documents/reminders are not shared by default.
- Test suspended accounts and link revocation, plus existing landlord payment/expense/arrears regression suites.
- Validate read-only UI at mobile viewport and with both Romanian and English locales before production rollout.

## Checkpoint

- Branch: `docs/renter-portal-design-checkpoint-20261009`.
- Scope: docs only. No migrations, auth logic, source code, deployments, or production credentials touched.
- Completed: owner-confirmed document deletion and corrected CSV export recorded in issue register; initial identity/access proposal.
- Tests: not run locally (documentation-only). Review GitHub CI on PR before merge.
- Unresolved: invitation token schema, verification and registration UX, preexisting `user_id` audit, lifecycle/revocation, explicit renter-visible data flags and approval for any authentication/migration change.
- Exact next task: obtain explicit approval for the restricted renter-only enrollment/authentication design, then implement the backend invitation link in a separate, reviewed PR with migration and allow/deny tests. Never implement implicit email matching.
