# Rentier Roadmap

This roadmap is driven by `docs/issue-register.md`. No phase is complete until the issue register is updated with the final status and evidence.

Every future Codex sprint must update `docs/issue-register.md` before commit.

Infrastructure and security tasks can be scheduled between product sprints when that lowers operational risk or fits a maintenance window. The product sequence below should not be reordered casually; move items only when the issue register records the reason and evidence.

## Phase 1 - Close old operational backlog

- OPS-01 - Download and verify an off-VPS backup copy on the home PC.
- OPS-02 - Apply Ubuntu updates and perform a controlled server reboot.

## Phase 2 - Landlord v1

The owner-approved product gate is defined in `docs/landlord-v1-scope.md`.

Completed foundations include production mail/password reset, `app.rentier.ro`, the public landing, Rentier-branded authenticated/auth UI, canonical app-host routing, and the internal `admin.rentier.ro` operational baseline.

Build the remaining Landlord v1 modules in this order unless the issue register records a reason to change it:

1. Documents: private property/lease uploads, categories, dates/expiry, download/delete and authorization.
2. Calendar and reminders.
3. Utilities and invoice tracking/ingestion.
4. Renter portal.
5. Romanian tax/CASS module with tax-year-versioned official-source logic.
6. Export and reporting.
7. Maintenance, property history, search/filtering and final multi-property usability polish.

Do not regress the existing rental/payment/expense calculations while adding these modules.

## Phase 3 - Public growth / SEO

After Landlord v1 is complete, build useful public resources on `rentier.ro` before the large platform expansion:

- Romanian landlord guides/resources;
- tax/CASS calculator;
- other high-value calculators;
- blog/resource library;
- technical SEO, metadata, canonical URLs, structured data, sitemap and internal linking.

Tax calculators must never ship with approximate or stale formulas. Version tax logic by year and verify it against current official Romanian sources.

## Phase 4 - Larger Rentier platform expansion

Only after Landlord v1 and the public growth stage are healthy should work begin on the larger multi-portal direction documented in `docs/product-platform-direction.md`, including:

- `home.rentier.ro`;
- broader shared property lifecycle;
- market/rental map;
- building/association administration.

Subscriptions, support tooling, audit logs and other SaaS capabilities remain separate backlog items and should be introduced when the product model requires them.
