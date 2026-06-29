# Project Rules

These rules guide Rentier development and AI-assisted work. They should be treated as project constraints unless a maintainer explicitly updates them.

## Coding Rules

- Prefer simple, readable Laravel and React code over clever abstractions.
- Follow existing project conventions before introducing new patterns.
- Keep changes small and scoped to the requested feature or fix.
- Use TypeScript types for frontend data boundaries and reusable UI components.
- Use Laravel Form Requests or equivalent validation patterns for write actions.
- Keep controllers thin. Move domain behavior into models, actions, services, policies, or jobs when complexity justifies it.
- Add tests for meaningful domain behavior, authorization rules, and regressions.
- Do not introduce packages unless the need is clear and approved.

## Architecture Rules

- Rentier is one Laravel application with one backend authentication system.
- Use Inertia React for app screens.
- Keep server-side domain rules in Laravel, not only in frontend state.
- Design for SQLite locally and PostgreSQL or MySQL later.
- Avoid hard-coding production-specific infrastructure choices during the MVP.
- Keep landlord and renter portal screens separate where it improves clarity, while sharing backend models and policies.
- Prefer explicit domain concepts over generic settings or metadata blobs for core rental workflows.

## Naming Rules

- Use `renter`, not `tenant`, in code, UI copy, database names, and documentation.
- Use `organization` or `workspace` for the business/account context.
- Laravel Teams represent Rentier Organizations or Workspaces.
- Do not use `team` in user-facing copy unless referring to Laravel internals or package conventions.
- Use clear domain names: `property`, `lease`, `payment`, `expense`, `utility_bill`, `maintenance_ticket`, `document`, `message`.
- Keep database table names plural and conventional.

## Auth and Role Rules

- Use one `users` table for all authenticated people.
- A user may be a landlord, renter, or both.
- Do not create separate authentication systems for landlords and renters.
- Do not create separate user tables for landlords and renters.
- Portal experience is a UI concern; authorization is a backend concern.
- Roles should describe what a user can do within an organization/workspace or relationship, not replace policy checks.
- Invitations should grant access intentionally and with clear scope.

## Policy and Authorization Rules

- Use Laravel Policies for protected domain actions.
- Do not rely only on role checks in controllers or frontend components.
- Check access to organization/workspace resources through policies.
- Check property, lease, payment, expense, utility, document, message, and maintenance access through policies.
- Policies should account for users who may be both landlords and renters.
- Frontend hiding of controls is not authorization.
- Tests should cover important allow and deny cases.

## Database Rules

- Keep migrations portable across SQLite, PostgreSQL, and MySQL where practical.
- Use foreign keys for core relationships.
- Prefer explicit status columns for workflow states that need filtering or reporting.
- Store money as integer minor units when implementation begins, with currency where needed.
- Store dates and timestamps using Laravel conventions.
- Avoid JSON columns for core searchable/reportable fields during the MVP.
- Add indexes for common ownership and lookup paths when tables are introduced.
- Do not model renters as Laravel Teams.

## Mobile-First UI Rules

- Design mobile-first from the beginning.
- Every core workflow must work on small screens before desktop enhancements are added.
- Use responsive layouts that can later adapt to Android and iOS app shells.
- Avoid desktop-only navigation assumptions.
- Keep forms short, grouped, and easy to complete on touch devices.
- Use accessible controls, labels, focus states, and error messages.
- Use icons for familiar actions when helpful, with accessible labels or tooltips.
- Avoid dense desktop tables as the only way to use a feature.

## AI and Codex Workflow Rules

- Read relevant files before editing.
- Do not change authentication logic unless explicitly requested.
- Do not run migrations unless explicitly requested.
- Do not install packages unless explicitly requested.
- Prefer documentation updates, focused patches, and explicit summaries.
- Preserve user changes in the working tree.
- Use project terminology consistently, especially `renter` and `organization/workspace`.
- Keep future implementation notes practical enough for another AI or developer to continue.
- When unsure, choose the smallest useful MVP-oriented step and document assumptions.

## Do Not Overbuild MVP Rules

- Build the landlord MVP before advanced renter, utility, or SaaS automation.
- Do not add complex accounting, subscriptions, analytics, or integrations before core workflows are usable.
- Do not design a generic multi-tenant platform when Rentier needs property rental workflows.
- Do not introduce elaborate role systems before policies and simple roles are proven sufficient.
- Do not automate utility provider workflows before manual utility bill tracking exists.
- Do not create separate apps for landlord and renter portals during the MVP.
- Do not optimize for edge cases before the common landlord and renter flows are clear.
