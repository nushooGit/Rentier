# Rentier

Rentier is a property rental management SaaS for landlords and renters. The product helps landlords manage properties, leases, rent payments, expenses, documents, maintenance, and communication while giving renters a clear portal for their rental relationship.

The project is intentionally MVP-focused. Build the landlord workflow first, keep the domain language clear, and leave room for renter portals, utility automation, and provider integrations after the core rental management experience is stable.

## Current Tech Stack

- Laravel 13
- PHP 8.3+
- Laravel Fortify for authentication
- Inertia.js with React 19
- TypeScript
- Vite
- Tailwind CSS 4
- Radix UI primitives
- lucide-react icons
- Pest for tests
- Laravel Pint, PHPStan/Larastan, ESLint, Prettier, TypeScript checks
- SQLite for local development
- PostgreSQL or MySQL planned for production

## Local Development Notes

- Current local URL: `http://127.0.0.1:8000`
- The optional `rentier.test` hostname depends on Laravel Herd parked paths and local DNS/hosts configuration.
- SQLite is the default local database direction. Keep migrations portable for later PostgreSQL or MySQL production deployments.
- Use one backend authentication system and one `users` table.
- The UI may expose separate landlord and renter portal experiences, but authentication remains shared.
- A user may be a landlord, renter, or both.
- Laravel Teams should represent Rentier Organizations or Workspaces, not renters.
- In product and code language, use `renter` instead of `tenant` to avoid confusion with SaaS tenancy terminology.
- Authorization must use Laravel Policies for protected domain actions.

## MVP Scope

Phase 1 should focus on a practical landlord MVP:

- Landlord onboarding into an organization/workspace.
- Basic property management.
- Lease tracking for renters assigned to properties.
- Rent payment records and status tracking.
- Expense records for properties.
- Basic document storage metadata.
- Basic maintenance ticket tracking.
- Mobile-first dashboard and workflows.

Do not overbuild automation, billing, marketplace, analytics, or provider integrations before the landlord MVP works end to end.

## Product Phases

1. Phase 1 MVP Landlord: core property, lease, payment, expense, document, and maintenance workflows for landlords.
2. Phase 2 Renter Portal: renter-facing access to leases, payment status, maintenance tickets, documents, and messages.
3. Phase 3 Utilities Automation: utility accounts, bills, readings, reminders, and manual bill workflows.
4. Phase 4 Official Provider Integrations: supported integrations with utility providers, payment processors, accounting, storage, and notifications.
5. Phase 5 Advanced SaaS Features: subscriptions, richer reporting, multi-workspace management, advanced roles, audit history, and enterprise controls.

## Basic Commands

Install dependencies:

```bash
composer install
npm install
```

Prepare local environment:

```bash
cp .env.example .env
php artisan key:generate
```

Run the app locally:

```bash
composer run dev
```

Run only the Laravel server:

```bash
php artisan serve
```

Run only Vite:

```bash
npm run dev
```

Build frontend assets:

```bash
npm run build
```

Run tests and checks:

```bash
composer test
composer run ci:check
npm run lint:check
npm run format:check
npm run types:check
```

Format and lint:

```bash
composer run lint
npm run lint
npm run format
```

Seed local demo data:

```bash
php artisan db:seed
```

The seeder creates or updates a local demo workspace with București properties,
renters, active contracts, rent payments, and expenses. It is intended for local
development and is not run automatically in production.

## Documentation

- [Project Rules](PROJECT_RULES.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Roadmap](docs/ROADMAP.md)
- [Data Model Draft](docs/DATA_MODEL_DRAFT.md)
- [Beta Deployment Readiness](docs/deployment-beta.md)
