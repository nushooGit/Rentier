# Data Model Draft

This is a planning draft, not a migration specification. Use it to guide future implementation while keeping the MVP focused and portable across SQLite, PostgreSQL, and MySQL.

## users

Represents every authenticated person.

Possible fields:

- `id`
- `name`
- `email`
- `email_verified_at`
- `password`
- authentication and remember token fields required by Laravel/Fortify
- profile fields added only when needed

Notes:

- One user may be a landlord, renter, or both.
- Do not create separate landlord and renter user tables.
- Portal access should come from relationships and policies.

## teams / organizations

Represents a Rentier organization/workspace. This may map to Laravel Teams internally.

Possible fields:

- `id`
- `name`
- `owner_id`
- `slug`
- `settings`
- timestamps

Notes:

- Organizations/workspaces are landlord-side business contexts.
- Renters are not teams.
- Prefer `organization` or `workspace` in Rentier code and UI unless a package requires `team`.

## team_user / organization_user

Represents membership in an organization/workspace.

Possible fields:

- `id`
- `team_id` or `organization_id`
- `user_id`
- `role`
- `status`
- timestamps

Notes:

- Roles may include owner, admin, manager, or staff.
- Roles do not replace Laravel Policies.
- If Laravel Teams requires `team_user`, keep product terminology mapped clearly.

## properties

Represents a rental property or unit managed by an organization/workspace.

Possible fields:

- `id`
- `organization_id`
- `name`
- `type`
- `address_line_1`
- `address_line_2`
- `city`
- `region`
- `postal_code`
- `country`
- `status`
- timestamps

Notes:

- Properties are scoped to an organization/workspace.
- Future modeling may split buildings and units if needed. Do not overbuild this before the MVP requires it.

## leases

Represents a rental agreement connecting renters to a property.

Possible fields:

- `id`
- `organization_id`
- `property_id`
- `primary_renter_id`
- `status`
- `start_date`
- `end_date`
- `rent_amount_minor`
- `currency`
- `rent_due_day`
- `deposit_amount_minor`
- `notes`
- timestamps

Notes:

- A lease may need multiple renters later. Start with a simple model if that is enough for MVP.
- Lease policies must protect both landlord and renter access paths.

## payments

Represents rent or lease-related payment records.

Possible fields:

- `id`
- `organization_id`
- `lease_id`
- `property_id`
- `renter_id`
- `type`
- `status`
- `amount_minor`
- `currency`
- `due_date`
- `paid_at`
- `reference`
- `notes`
- timestamps

Notes:

- MVP can use manual records.
- Store money as integer minor units.
- Future processor data should be added without making payment records provider-specific.

## expenses

Represents landlord costs associated with a property.

Possible fields:

- `id`
- `organization_id`
- `property_id`
- `category`
- `status`
- `amount_minor`
- `currency`
- `expense_date`
- `vendor`
- `description`
- timestamps

Notes:

- Useful for property profitability and reports.
- Attach receipts through `documents` when document storage is implemented.

## utility_providers

Represents companies or services that provide utilities.

Possible fields:

- `id`
- `name`
- `type`
- `country`
- `website`
- `support_phone`
- timestamps

Notes:

- Start manual.
- Official integration metadata can be added later.

## utility_accounts

Represents a utility account for a property or lease.

Possible fields:

- `id`
- `organization_id`
- `property_id`
- `lease_id`
- `utility_provider_id`
- `account_number`
- `service_type`
- `responsible_party`
- `status`
- timestamps

Notes:

- `responsible_party` can describe landlord or renter responsibility.
- Avoid provider-specific complexity during the MVP.

## utility_bills

Represents a utility bill or facture.

Possible fields:

- `id`
- `organization_id`
- `utility_account_id`
- `property_id`
- `lease_id`
- `billing_period_start`
- `billing_period_end`
- `issue_date`
- `due_date`
- `amount_minor`
- `currency`
- `status`
- `provider_reference`
- timestamps

Notes:

- Use `utility_bills` in code even when product copy mentions factures/facturi.
- Attach bill files through `documents`.

## utility_readings

Represents meter readings for utility accounts.

Possible fields:

- `id`
- `organization_id`
- `utility_account_id`
- `property_id`
- `reading_date`
- `reading_value`
- `unit`
- `source`
- `notes`
- timestamps

Notes:

- Useful for water, gas, electricity, or heating workflows.
- Keep readings manual until provider integrations are approved.

## maintenance_tickets

Represents repair or issue requests.

Possible fields:

- `id`
- `organization_id`
- `property_id`
- `lease_id`
- `reported_by_user_id`
- `assigned_to_user_id`
- `title`
- `description`
- `priority`
- `status`
- `reported_at`
- `resolved_at`
- timestamps

Notes:

- Renters should only see tickets connected to their lease or property relationship.
- Landlords and staff should be authorized through organization/workspace policies.

## documents

Represents uploaded or generated document metadata.

Possible fields:

- `id`
- `organization_id`
- `uploaded_by_user_id`
- `documentable_type`
- `documentable_id`
- `name`
- `category`
- `disk`
- `path`
- `mime_type`
- `size_bytes`
- `visibility`
- timestamps

Notes:

- Use polymorphic relationships if documents attach to many entity types.
- Document access must be policy controlled.

## messages

Represents communication between users in a rental context.

Possible fields:

- `id`
- `organization_id`
- `property_id`
- `lease_id`
- `sender_id`
- `subject`
- `body`
- `status`
- timestamps

Notes:

- Keep MVP messaging simple.
- If threaded conversations are needed later, introduce conversation tables deliberately.

## notifications

Represents persisted notification records where Laravel's notification system or a custom table is used.

Possible fields:

- `id`
- `user_id`
- `type`
- `data`
- `read_at`
- timestamps

Notes:

- Use notifications for invitations, payment reminders, maintenance updates, document sharing, and utility bill reminders.
- Avoid business-critical state existing only inside notification payloads.

## invitations

Represents invitations to join an organization/workspace or rental relationship.

Possible fields:

- `id`
- `organization_id`
- `email`
- `invited_by_user_id`
- `role`
- `intended_portal`
- `property_id`
- `lease_id`
- `token`
- `status`
- `expires_at`
- `accepted_at`
- timestamps

Notes:

- Invitations can invite staff to an organization/workspace or renters to a lease relationship.
- Invitation acceptance must create the right relationship and remain policy controlled.
- Tokens should be secure and expire.
