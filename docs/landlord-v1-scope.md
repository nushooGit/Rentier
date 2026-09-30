# Rentier Landlord v1 Scope

Status: owner-approved product gate, 2026-09-30.

## Goal

Before starting the large `home.rentier.ro` / market / building-administration expansion, `app.rentier.ro` must become a complete and sellable Romanian-first landlord product.

This is a sequencing rule: future platform ideas remain valid, but they do not outrank finishing the landlord application.

## Existing core to preserve and polish

The current landlord core remains the foundation:

- properties;
- leases/contracts;
- renters;
- rent and guarantee payments;
- expenses and settlements;
- arrears/advance allocation;
- dashboard;
- workspaces and access control;
- Romanian auth and responsive application shell.

Do not regress the existing financial behavior while adding the v1 modules below.

## Required Landlord v1 modules

### Documents

Support uploaded documents attached to the appropriate workspace/property/lease context.

Initial document categories should cover at least:

- rental contracts;
- addenda / acte adiționale;
- comodat agreements;
- handover / condition reports;
- invoices and receipts;
- property/ownership documents where appropriate;
- other user-defined supporting documents.

Important capabilities:

- upload/download;
- category/type;
- related property and optional lease;
- document date;
- optional expiry/reminder date;
- practical search/filtering;
- authorization enforced server-side.

Template generation and e-signature are later enhancements, not required for the first document MVP.

### Calendar and reminders

Provide an operational calendar/timeline that answers: **what do I need to collect or do, and when?**

At minimum:

- rent due date with property and renter;
- overdue rent;
- lease start/end/expiry;
- utility invoice due dates;
- maintenance/revision dates;
- insurance/tax deadlines where tracked;
- user-created reminders.

Calendar data should be generated from domain records rather than duplicated manually whenever possible.

### Utilities and invoices

Build utilities as a reusable domain capability.

Initial scope:

- utility provider;
- customer/account identifier;
- property association;
- optional lease/renter responsibility;
- invoice number;
- billing period;
- issue/due date;
- amount/currency;
- paid/unpaid or settlement state;
- attachment;
- meter readings and consumption where useful.

Automation progression:

1. manual account setup;
2. manual invoice upload;
3. PDF text extraction;
4. OCR/vision for scans/images;
5. confidence validation/user confirmation;
6. per-property invoice inbox / email forwarding;
7. official provider integrations when available and appropriate.

Do not rely on scraping provider portals or storing provider passwords.

### Renter portal

A renter should be able to see only the data intentionally exposed to them.

Initial portal should include:

- active lease summary;
- rent amount/due date;
- payment status and history;
- guarantee/deposit context where appropriate;
- utilities/charges assigned to the renter;
- renter-visible documents;
- important dates/reminders;
- later maintenance communication as the maintenance module matures.

Keep authorization centralized in Laravel policies. Hiding frontend controls is not sufficient.

### Romanian tax and CASS module

Provide a landlord-oriented annual tax workspace.

Potential scope:

- rental income summary by year;
- relevant recorded amounts used by the calculation;
- applicable tax/CASS thresholds and deadlines;
- estimated obligations;
- clear explanation of what is an estimate versus a filed/paid amount;
- historical tax-year snapshots;
- exportable summary.

This is consequential financial logic. Rules must be:
- versioned by tax year;
- sourced from official Romanian rules before implementation;
- covered by focused tests for thresholds/boundaries;
- reviewed before production whenever formulas change.

Do not hard-code a single timeless formula.

### Export and reporting

Users should be able to take their data out of Rentier.

Initial exports should include where useful:

- properties;
- leases;
- rent payments;
- expenses;
- utility bills;
- annual/property financial summaries;
- renter/payment history.

Preferred formats:
- CSV/XLSX for structured data;
- PDF for human-readable summaries where useful;
- ZIP/archive export for property/lease documents where appropriate.

Accounting/ANAF-specific export formats should be added only after the exact required format is confirmed.

### Maintenance and property history

Before Landlord v1 is considered complete, add a practical maintenance/history layer:

- issue/work item;
- property;
- status;
- dates;
- cost;
- notes;
- attachments/documents;
- history of completed work.

A property should also have a useful chronological history/timeline across important events where feasible.

### Search, filtering and usability

The product must remain usable for landlords with more than a handful of properties.

Provide practical filtering/search across key lists and keep core workflows mobile-first.

## Landlord v1 completion gate

Do not declare Landlord v1 complete merely because each route exists.

Completion requires:
- core workflows are usable on mobile and desktop;
- authorization boundaries have allow/deny tests;
- financial/tax logic has focused regression coverage;
- upload/export flows have size/type/security constraints;
- renter portal does not leak landlord/workspace data;
- production smoke checks exist for the highest-risk workflows;
- issue register records final verification.

## What comes immediately after Landlord v1

Before the large product-family expansion, build a public growth/SEO layer on `rentier.ro`:

- evergreen landlord resources;
- tax/CASS calculator;
- other high-value calculators/tools;
- blog/resource library;
- technical SEO and internal linking.

Only after this stage should the roadmap move into the larger `home.rentier.ro`, market/map and building-administration expansion.
