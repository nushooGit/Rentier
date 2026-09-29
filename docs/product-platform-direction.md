# Rentier Platform Direction

Status: product direction approved by owner on 2026-09-29.

## Product thesis

Rentier should evolve from a landlord-only rental tracker into a Romanian-first property operating platform.

The central product object is the **property**, not a portal. A property should keep one durable operational and financial history even when its use changes over time: owner-occupied, secondary home, vacant, rented, or managed in another Rentier surface.

The long-term product principle is:

**one Rentier account -> one identity -> many properties -> many portal experiences**

Portal separation is a user-experience concern, not a reason to duplicate the backend, users, database, or property records.

## Portal model

Planned product surfaces:

- `rentier.ro` — public website and, later, public market/rental discovery.
- `app.rentier.ro` — landlord/rental management. Keep the current application host; do not rename or migrate it again merely for branding.
- `home.rentier.ro` — owner-occupied/secondary-home management: bills, utilities, meters, maintenance, documents, warranties, insurance, taxes, household costs and asset history.
- `admin.rentier.ro` — internal Rentier platform administration only.
- A future building/association administration portal, working name `bloc.rentier.ro`, only after the core property and utilities model is mature.
- A renter-facing portal may remain within the shared platform initially; a dedicated renter hostname should be introduced only if the product experience justifies it.

The same user may have access to multiple portals at once. Example: a user may manage their own house in Home while simultaneously managing rented apartments in the landlord portal.

## Shared identity and portal switching

Do not create separate accounts for Home, Landlord, renter or future building-management products.

A user who has access to multiple Rentier surfaces should be able to switch products without entering credentials repeatedly.

Short term:
- keep the existing single Laravel authentication backend and one `users` table;
- preserve host-only sessions;
- expose a clear portal/product switcher in the UI when multiple products are available.

Long term:
- introduce an identity broker such as `auth.rentier.ro` if seamless cross-subdomain sign-in is needed;
- use short-lived signed handoff/OIDC-style flows rather than broadening cookies to `.rentier.ro`;
- keep `admin.rentier.ro` outside normal consumer SSO unless separately reviewed.

## Property lifecycle

A property must not be duplicated when its use changes.

Example lifecycle:

1. User adds a house in `home.rentier.ro`.
2. Rentier accumulates bills, utility readings, maintenance, documents, equipment and cost history.
3. The owner later rents the house.
4. The same property becomes available in the landlord portal.
5. Historical Home data remains attached to the same property.
6. Rental-specific data such as leases, renters, rent payments and deposits is added without losing the prior history.

Future data modelling should therefore distinguish a property's identity from its current usage/occupancy mode.

## Home product direction

The Home surface should eventually cover:

- utility accounts and invoices;
- meter readings and consumption history;
- bill due dates and payment status;
- maintenance and recurring service reminders;
- equipment/appliance register and warranty expiry;
- insurance and property-tax reminders;
- property documents;
- renovations and repair history;
- recurring and total ownership costs;
- multiple owner-occupied or secondary homes.

The interface should not expose landlord-specific concepts unless a property is also used for rental.

## Utility and invoice platform

Utilities are a shared platform capability used by Home and Landlord.

Target progression:

1. Manual utility account setup.
2. Manual invoice upload.
3. PDF text extraction where possible.
4. OCR/vision for scanned/image invoices.
5. Structured extraction: provider, customer/account identifier, invoice number, issue date, due date, billing period, amount, meter readings and consumption where available.
6. Confidence checks and user confirmation for uncertain fields.
7. Per-property invoice inbox / forwarding address so supplier emails can be processed automatically.
8. Official supplier/API integrations only where supported and legally appropriate.

Do not make the platform depend on scraping supplier portals or storing supplier portal passwords.

For rental properties, utility invoices should be reusable in renter/landlord responsibility and settlement flows instead of being entered twice.

## Property operating record

Rentier should progressively become the durable record of a property's operational life:

- invoices and utilities;
- meters and consumption;
- leases and occupancy;
- payments and deposits;
- expenses and reimbursements;
- documents;
- maintenance and repairs;
- appliances/equipment and warranties;
- inspections;
- move-in/move-out condition evidence;
- insurance, taxes and recurring obligations;
- historical ownership/operating cost.

A future digital handover/condition report can include room-by-room photos, inventory, existing damage, meter readings, keys and signatures, then compare move-in and move-out states.

## Property economics

Rentier should distinguish cash received from real property performance.

Future analytics may include:

- actual rent collected;
- vacancy periods;
- owner-paid costs;
- maintenance and repair cost;
- tax/insurance cost where tracked;
- utility amounts borne by owner;
- gross and net yield;
- real annual ownership/rental cost.

Financial logic remains high-risk and must be implemented and reviewed in focused increments.

## Market / rental map direction

A future public market surface can use maps and rental search, but a simple map of listing prices is not sufficient differentiation.

Potential long-term value:

- public asking-rent data where legally sourced;
- anonymized and sufficiently aggregated Rentier contract data;
- neighborhood rental ranges;
- actual contracted rent versus public asking rent;
- owner-side market comparison for an existing property;
- renter-side affordability/location search;
- property yield context.

Never expose individual contract or user data. Any Rentier-derived market statistics require privacy thresholds and aggregation rules before implementation.

## Technical guardrails

- Remain a modular Laravel monolith unless scale/product constraints justify a split.
- One primary database and one shared user identity.
- Shared property, utility, document and maintenance domain models across portals.
- Portal-specific routing, layouts and authorization.
- Do not duplicate a property simply because it appears in more than one portal.
- Keep platform-admin privileges separate from customer/workspace roles.
- Prefer host-only sessions.
- Do not add cross-subdomain shared cookies without a separate security review.
- Build new domain foundations before adding a large number of disconnected UI features.

## Near-term sequencing

This document is direction, not permission to implement everything at once.

Current near-term sequence:

1. Finish the current `admin.rentier.ro` safe foundation.
2. Design the shared property lifecycle and utility/invoice data model.
3. Define the smallest useful Home MVP.
4. Implement Home incrementally without regressing the existing landlord product.
5. Add invoice ingestion/automation in stages.
6. Treat the public rental market/map as a later growth/data product once Rentier has enough trustworthy data and sourcing.
7. Treat building/association administration as a later portal built on the shared property/utility foundations.

Every implementation increment must still follow `PROJECT_RULES.md`, the issue register, tests, CI and deployment safeguards.
