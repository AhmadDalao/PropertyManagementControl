# Property Control System Status

Updated: July 21, 2026

## Current position

The application is an operational MVP release candidate. Core property, tenant, lease, payment, maintenance, document, reporting, CMS, localization, map, permission, and audit workflows are implemented. The remaining launch work is operational configuration and business approval, not another UI rewrite.

## Working product scope

- Superadmin can manage platform users, portfolios, assets, CMS content, wording, showcase data, reports, media, and audit history.
- Owners and property managers can manage their scoped assets, tenants, leases, manual payments, maintenance, expenses, documents, and reports.
- Tenants can see their active rental, contract period, paid and remaining amounts, PDF documents, payment history, and maintenance requests.
- Assets support property, building, floor, unit, and space hierarchies with ownership, management, valuation, occupancy, and map coordinates.
- Lease installments distinguish current arrears from future contract balance. Payment allocation and reversal use database transactions and row locks.
- Contracts, statements, and receipts are private PDF documents with bilingual Arabic shaping. Reports export as real `.xlsx` workbooks.
- English and Arabic administration, public content, RTL layout, editable wording, localized map data, and CMS content are supported.
- Resource indexes use server pagination, scoped search and filters, desktop tables, compact mobile cards, exports, and detail pages.

## UI and quality status

- Browser coverage passes at 390px, 768px, 1024px, and 1440px with no page-level horizontal overflow.
- Mobile navigation locks background scrolling, restores focus, and keeps the topbar at 64px.
- Long reports are split into focused tabs and responsive card grids. Detail pages use query-backed tabs and single-column tablet/mobile layouts.
- Automated status: 164 PHP tests with 7,280 assertions and 18 Playwright/axe scenarios pass.
- PHP syntax, Pint, TypeScript, ESLint, Prettier, Vite, Composer audit, pnpm audit, route discovery, and migrations pass.
- The main CSS bundle is 320.71 KB before gzip. Further CSS reduction is useful, but it is not a launch blocker.
- PHPStan has 357 accepted legacy findings captured in a baseline, down from 872. The Asset, Maintenance, Lease, Payment, and shared-module extractions add zero findings in their touched slices.
- Assets are the first complete vertical feature module: its controller is now a thin 103-line adapter and its React index is a 42-line composer.
- Maintenance is the second complete vertical feature module: its controller is now a 106-line adapter and its React index is a 51-line composer. Tenant responses no longer expose internal updates or owner expense data.
- Leases are the third complete vertical feature module: its controller is now a 117-line adapter and its React index is a 42-line composer. Lease creation stores canonical morph aliases, alias records enforce exclusivity and occupancy, and tenant details omit internal notes, actions, documents, and history.
- Payments are the fourth complete vertical feature module: its controller is now a 98-line adapter and its React index is a 42-line composer. The lease is the source of truth for portfolio, tenant, and currency; void transitions are locked; tenant details hide internal data; and receipt replacement uses safe private PDF paths.

## Required before real tenant onboarding

1. Run production on PHP 8.4.1 or newer with `calendar` and `mbstring` enabled.
2. Configure and test production SMTP so password-reset links are delivered.
3. Run Laravel's scheduler every minute. It drains the database queue and synchronizes expired leases, occupancy, and overdue installments.
4. Verify automated database and private document backups, then perform one restore drill.
5. Approve the English and Arabic lease clauses with the property owner's legal adviser. The system intentionally does not invent legal wording.
6. Confirm each portfolio's opening balances, currency, billing rules, user permissions, and document retention policy.
7. Complete production acceptance tests with one superadmin, owner, manager, and tenant account before inviting real users.

## Deliberate MVP limits

- Payments are recorded manually; there is no payment gateway or bank reconciliation.
- Sign-off is a PDF upload/download workflow; there is no cryptographic e-signature engine.
- Finance is operational income, arrears, expenses, and net position, not double-entry accounting.
- Notifications use queued email infrastructure, but there is no full notification center or SMS/WhatsApp integration.
- Shared hosting is supported through database queues and cron; long-running workers, Redis, Horizon, and realtime sockets are intentionally excluded.

## Next goal

Continue the vertical modular refactor with Documents, then Tenants and Expenses. Keep routes and schemas stable, ship one tested module at a time, and do not delay the production onboarding work for SMTP, backup restore, legal templates, and a controlled pilot property.
