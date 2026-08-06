# Rentier Roadmap

This roadmap is driven by `docs/issue-register.md`. No phase is complete until the issue register is updated with the final status and evidence.

Every future Codex sprint must update `docs/issue-register.md` before commit.

The previous broad roadmap phases were reorganized into this issue-driven sequence: landlord foundations are represented by the closed register items, renter portal and utilities work are deferred, and advanced SaaS/platform work remains later-phase work.

## Phase 1 - Close old backlog

- EXP-02 - Active-lease association for tenant-involved expenses. Verified locally; pending final UI/production smoke verification before closing.
- DATE-02 - Romanian create/edit date-picker UX.
- UI-01 - Correct `Scazut din chirie` to `Scăzut din chirie`. Verified locally; pending final UI/production smoke verification before closing.
- UI-02 - Review/rename the generic or cash rent-collected label. Verified locally; pending final UI/production smoke verification before closing.
- PROP-02 - Add total surface area in m2.
- SEC-01 - Controlled dependency review for npm audit advisories; do not use blind `npm audit fix`.
- SESSION-01 - Review `SESSION_CONNECTION`, `SESSION_STORE`, and `SESSION_DOMAIN`, especially before `app.rentier.ro`.
- OPS-01 - Download and verify an off-VPS backup copy on the home PC.
- OPS-02 - Apply Ubuntu updates and perform a controlled server reboot.
- OPS-03 - Complete SSH hardening and verify `PasswordAuthentication` is disabled.

Infrastructure and security tasks can be scheduled between product sprints when that lowers operational risk or fits a maintenance window.
The product sequence below should not be reordered casually; move items only when the issue register records the reason and evidence.

## Phase 2 - Healthy product sequence

1. Production email and password reset.
2. Move the authenticated application to `app.rentier.ro`.
3. Build the public landing page on `rentier.ro`.
4. Minimal internal admin for users, workspaces, activation/suspension, and statistics.
5. Later subscriptions, support, audit, and advanced tools.

Deferred advanced work includes support tooling, audit logs, documents/invoices, tenant portal, utilities/maintenance, and ANAF research.
