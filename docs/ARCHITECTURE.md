# Architecture

Rentier is a Laravel property operating platform, currently centered on rental management. The backend owns authentication, authorization, data integrity, and domain rules. The frontend uses Inertia React to provide mobile-first portal experiences over the same backend. The approved long-term multi-portal direction is documented in [Rentier Platform Direction](product-platform-direction.md).

## High-Level Architecture

- Laravel handles routing, controllers, validation, policies, models, jobs, notifications, and persistence.
- Inertia connects Laravel routes to React pages without a separate API application for the MVP.
- React and TypeScript handle interactive screens, forms, and responsive layouts.
- Tailwind CSS provides mobile-first styling.
- SQLite is used locally. PostgreSQL is the target beta production database.
- The application should remain a modular monolith until product needs justify extraction.

## Auth Strategy

- Use one backend authentication system.
- Use one `users` table.
- Do not split landlord and renter authentication.
- A single user account may act as a landlord, renter, or both.
- Portal selection should be based on authorized relationships and UI context, not separate login systems.
- Authentication changes should be deliberate and tested because they affect all portal experiences.

## Internal Platform Admin Strategy

Internal platform administration is a separate operational surface from landlord workspace membership.

- Use the same Laravel authentication system and the same `users` table.
- Serve the internal surface from a dedicated host configured by `RENTIER_ADMIN_URL` (production target: `https://admin.rentier.ro`).
- Keep session cookies host-only. Logging into `app.rentier.ro` must not silently create an authenticated session on `admin.rentier.ro`, or vice versa.
- Platform-admin access must be independent from workspace owner/member roles. The first MVP increment uses a deployment-only email allowlist (`RENTIER_PLATFORM_ADMIN_EMAILS`) so no workspace role can grant platform access.
- Do not expose normal landlord workspace routes from the admin host.
- The current MVP admin baseline includes user/workspace visibility, aggregate statistics and separately reviewed user/workspace suspension/reactivation. Keep future subscription/billing controls separate until a real subscription model exists.

## Landlord and Renter Portal Strategy

The product may expose two primary portal experiences:

- Landlord portal: manage organizations/workspaces, properties, leases, rent payments, expenses, documents, utility bills, maintenance, messages, and invitations.
- Renter portal: view lease details, payment status, utility responsibilities, documents, messages, and maintenance tickets.

Both portals should use shared backend resources and Laravel Policies. UI routes and navigation may differ, but the authorization model must remain centralized.

## Organization and Workspace Model

Laravel Teams should be treated as Rentier Organizations or Workspaces.

- An organization/workspace represents the landlord-side business context.
- Landlords and staff can belong to an organization/workspace.
- Properties belong to an organization/workspace.
- Access to properties and related records is scoped through the organization/workspace and policies.
- Renters are users connected through leases or invitations, not teams.

Use `organization` or `workspace` in product language. Use package-specific `team` naming only where required by Laravel or installed scaffolding.

## Core Rental Concepts

Properties are the central landlord-managed assets. A property may have leases, payments, expenses, utility accounts, documents, maintenance tickets, and messages.

Leases connect renters to a property for a defined rental period. A lease should support status tracking, rent amount, due day, deposit details, start and end dates, and related documents.

Payments track expected or received rent and other lease-related charges. The MVP can begin with manual payment records and statuses before adding payment processor integrations.

Expenses track landlord costs for a property, such as repairs, supplies, taxes, services, or utilities paid by the landlord. Expenses should be reportable by property and date.

Maintenance tickets track renter or landlord reported issues, status, priority, assignment, notes, documents, and resolution.

Documents store metadata and relationships for uploaded files such as lease agreements, invoices, receipts, notices, and property documents where appropriate. Files are private by default and are served only through authorized application routes.

Messages support communication between landlords, renters, and possibly organization members. Keep messaging simple during the MVP.

Notifications support important events such as invitations, payment reminders, maintenance updates, and document availability.

## Utilities and Factures Strategy

Utility support should start with manual tracking before provider automation.

In Romanian product context, utility invoices may be referred to as factures/facturi. In code and database naming, prefer clear English names such as `utility_bills`, `utility_accounts`, and `utility_readings`.

Recommended progression:

1. Track utility providers manually.
2. Track utility accounts for each property or lease.
3. Record utility bills with billing periods, due dates, amounts, status, and attachments.
4. Extract embedded text from digital PDF invoices and offer confidence-scored form suggestions.
5. Add OCR/vision for scans and images only after digital-PDF extraction quality is measured.
6. Record meter readings where useful.
7. Add reminders and renter visibility.
8. Add official provider integrations only after manual workflows are proven.

The first invoice-reader increment is self-hosted. Production images include Poppler's `pdftotext` binary, invoked through Symfony Process with an argument array, a page limit and a short timeout. Analysis uses the request's temporary upload and does not persist a document or utility bill. Parsed values are suggestions only; the existing bill form remains the confirmation boundary and the user must save explicitly. No provider passwords or external AI/document APIs are required for this increment.

Avoid scraping or unofficial integrations unless explicitly approved and legally reviewed.

## Future Integrations

Potential integrations should be isolated behind clear service boundaries when implemented:

- Payment processors for rent collection.
- Utility providers for official bill import or account sync.
- Email and SMS providers for notifications.
- Object storage for documents.
- Accounting exports or integrations.
- E-signature providers for leases and documents.
- Identity or verification services where legally required.

Do not add integration abstractions before the MVP needs them.

## Mobile, Tablet, and Desktop Strategy

Rentier must be mobile-first and responsive from the beginning because it may later become Android and iOS apps.

- Mobile: core workflows must be usable on small screens with touch-friendly controls.
- Tablet: layouts may use split views, wider forms, and side panels where helpful.
- Desktop: dashboards, tables, and reporting can use denser layouts, but must not be the only usable experience.
- Navigation should support portal switching and organization/workspace context without assuming a wide sidebar.
- Use responsive components that can survive future app-shell embedding.


## Document Storage Strategy

The Landlord v1 document module stores document metadata in the application database and file bytes on Laravel's private `local` disk under `storage/app/private`.

- Never expose uploaded documents through the public storage symlink.
- Download/delete authorization must be enforced through Laravel policies and workspace scoping.
- Production local-file storage must live on a persistent Coolify volume before real documents are accepted; container-local ephemeral storage is not sufficient.
- Keep the stored disk/path on each document so a later move to S3-compatible object storage does not require changing the document domain model.
- Do not add OCR, e-signature or provider integrations to the initial document MVP.

## Product Sequencing Rule

The approved sequence is:

1. complete the landlord product gate defined in `docs/landlord-v1-scope.md`;
2. build a focused public growth/SEO layer on `rentier.ro`;
3. only then begin the larger Home/Market/building-administration expansion.

Shared domain design should still avoid choices that block future Home reuse, especially for documents, utilities, invoices, maintenance and property history.


## Localization Strategy

Rentier's implementation language is English even when the default product experience is Romanian.

- Keep code identifiers, database/schema names, routes, translation keys, tests and technical documentation in English.
- Never introduce Romanian class, variable, function or database names to support localized UI copy.
- User-facing copy belongs in translation resources and should be selected by locale at runtime.
- Romanian (`ro`) is the default interface locale; English (`en`) is the first optional locale.
- Backend validation, notifications and emails should use the same resolved locale as the user-facing request where practical.
- Locale preference should persist across sessions without broadening authentication/session-cookie scope.
- Dates, currency and number formatting should follow the active locale while domain values remain locale-neutral.
