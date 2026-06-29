# Architecture

Rentier is a Laravel SaaS application for property rental management. The backend owns authentication, authorization, data integrity, and domain rules. The frontend uses Inertia React to provide mobile-first landlord and renter portal experiences over the same backend.

## High-Level Architecture

- Laravel handles routing, controllers, validation, policies, models, jobs, notifications, and persistence.
- Inertia connects Laravel routes to React pages without a separate API application for the MVP.
- React and TypeScript handle interactive screens, forms, and responsive layouts.
- Tailwind CSS provides mobile-first styling.
- SQLite is used locally. PostgreSQL or MySQL should be supported later in production.
- The application should remain a modular monolith until product needs justify extraction.

## Auth Strategy

- Use one backend authentication system.
- Use one `users` table.
- Do not split landlord and renter authentication.
- A single user account may act as a landlord, renter, or both.
- Portal selection should be based on authorized relationships and UI context, not separate login systems.
- Authentication changes should be deliberate and tested because they affect all portal experiences.

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

Documents store metadata and relationships for uploaded files such as lease agreements, invoices, receipts, notices, and identity or compliance documents where appropriate.

Messages support communication between landlords, renters, and possibly organization members. Keep messaging simple during the MVP.

Notifications support important events such as invitations, payment reminders, maintenance updates, and document availability.

## Utilities and Factures Strategy

Utility support should start with manual tracking before provider automation.

In Romanian product context, utility invoices may be referred to as factures/facturi. In code and database naming, prefer clear English names such as `utility_bills`, `utility_accounts`, and `utility_readings`.

Recommended progression:

1. Track utility providers manually.
2. Track utility accounts for each property or lease.
3. Record utility bills with billing periods, due dates, amounts, status, and attachments.
4. Record meter readings where useful.
5. Add reminders and renter visibility.
6. Add official provider integrations only after manual workflows are proven.

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
