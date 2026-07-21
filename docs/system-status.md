# Property Control System Status

Updated: July 22, 2026

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
- Automated status: 295 PHP tests with 13,168 assertions and 28 Playwright/axe scenarios pass.
- PHP syntax, Pint, TypeScript, ESLint, Prettier, Vite, Composer audit, pnpm audit, route discovery, and migrations pass.
- The main CSS bundle is 322.51 KB before gzip, with Media styles loaded as a separate 3.18 KB route chunk. Further CSS reduction is useful, but it is not a launch blocker.
- PHPStan has 49 accepted legacy baseline entries, down from 872. The Asset, Maintenance, Lease, Payment, Document, Tenant, Expense, User, Portfolio, CMS, Media, Report, Audit, Search, Export, Wording, Dashboard, and shared-module extractions add zero findings in their touched slices.
- Assets are the first complete vertical feature module: its controller is now a thin 103-line adapter and its React index is a 42-line composer.
- Maintenance is the second complete vertical feature module: its controller is now a 106-line adapter and its React index is a 51-line composer. Tenant responses no longer expose internal updates or owner expense data.
- Leases are the third complete vertical feature module: its controller is now a 117-line adapter and its React index is a 42-line composer. Lease creation stores canonical morph aliases, alias records enforce exclusivity and occupancy, and tenant details omit internal notes, actions, documents, and history.
- Payments are the fourth complete vertical feature module: its controller is now a 98-line adapter and its React index is a 42-line composer. The lease is the source of truth for portfolio, tenant, and currency; void transitions are locked; tenant details hide internal data; and receipt replacement uses safe private PDF paths.
- Documents are the fifth complete vertical feature module: its controller is now a 95-line adapter and its React index is a 37-line composer. Uploads require a real PDF signature, attachments cannot be reassigned during edit, private server paths are not exposed, and tenant downloads are restricted to approved portal-visible files on their own leases or payments.
- The Document workspace has structured editable EN/AR wording, scoped filters and XLSX export, 10/25/50/100 pagination coverage, desktop tables, mobile cards, and a two-screen mobile layout with no horizontal overflow in the tested local dataset.
- Tenants are the sixth complete vertical feature module: its controller is now an 83-line adapter and its React index is a 42-line composer. Tenant and portal-user changes are transactional, portfolio access is enforced on direct actions, active leases block unsafe archiving, detail history is bounded, and the workspace has structured EN/AR wording with responsive mobile cards.
- Expenses are the seventh complete vertical feature module: its controller is now an 81-line adapter and its React index is a 45-line composer. Expense changes use row locks, portfolio references and currency are authoritative, voiding is terminal, mixed-currency totals are explicit, stored legacy/showcase categories remain editable in EN/AR, and the index no longer ships unbounded form-option data.
- Users are the eighth complete vertical feature module: its controller is now an 85-line adapter and its React index is a 37-line composer. Directory queries, exports, global search, detail links, role assignment, portfolio ownership, tenant-role transitions, and account status changes now share one authorization boundary, preventing managers from seeing owners or peer managers and blocking unsafe ownership or active-lease changes.
- Portfolios are the ninth complete vertical feature module: its controller is now a 91-line adapter and its React index is a 44-line composer. Indexes, exports, exact-code search, forms, details, module visibility, ownership, and archive rules now share one scoped boundary; detail relationships are bounded; mixed-currency totals are explicit; and owners cannot reactivate or archive records through unsafe status edits.
- CMS is the tenth complete vertical feature module: page, section, composition, navigation, public-query, form, and workspace responsibilities now live in dedicated actions, requests, presenters, and queries. The 476-line page controller is now a 166-line route adapter, the 179-line navigation controller is 55 lines, the 661-line builder is a 60-line composer, and the former monolithic section schema is exposed through a 15-line module barrel. Public pages require published and visible state, archived sections cannot leak into pages, reorder payloads must match the exact composition, navigation cycles are rejected, and the focused page/section/navigation workspace is verified in English and Arabic at mobile and desktop sizes.
- Media is the eleventh complete vertical feature module: its controller is now an 86-line adapter and its React index is a 37-line composer. Scoped queries, image validation, public/private storage moves, authorized file responses, forms, details, CMS usage guards, and the CMS picker live inside the module. CMS stores portable `/storage/...` image references, rejects executable formats, prevents hiding or deleting images in active use, and provides an overflow-free EN/AR picker at mobile and desktop sizes.
- Reports are the twelfth complete vertical feature module: its controller is now a 57-line adapter and its React entry is a 131-line composer. Query validation prevents broken date ranges, report and preset portfolio filters share one access boundary, saved views expose only legal actions, default views are deterministic, top-asset cards open the actual asset, and the card-only EN/AR workspace plus real `.xlsx` export passes mobile and desktop accessibility checks.
- Audit is the thirteenth complete vertical feature module: its controller is now a 34-line adapter and its React entry is a 35-line composer. Portfolio and actor filters share one access boundary, malformed dates are rejected, event facets respect the other active filters, all logged model types are registered, affected records open directly, sensitive keys remain suppressed, and the localized mobile-card/desktop-table workspace exports a real scoped `.xlsx` workbook.
- Global search and resource export are now modular platform infrastructure. The search controller is 22 lines, the export controller is 21 lines, every result/export belongs to its feature module, and index screens share the exact same scoped filters as their workbooks. Search input validation, tenant export denial, module visibility, Arabic groups, direct exact-code links, and portfolio isolation are covered by feature and browser tests.
- The property map is a complete Assets-owned slice. Its route controller remains a 23-line adapter, the former 406-line presenter is a 57-line orchestrator over focused source/activity queries and payload presenters, and the former 825-line React workspace is an 87-line composer. Malformed portfolio filters are rejected, the payload remains capped at 40 top-level markers, and architecture plus EN/AR browser tests protect Leaflet behavior, clustering, filtering, selection, and the paginated directory.
- Page Wording is a complete modular control surface. Its controller is a 49-line adapter, its public catalog is a 73-line compatibility facade, its completeness service is an 85-line orchestrator over four content domains, and its React entry is a 79-line composer. Invalid filters and unauthorized writes are rejected, required placeholders cannot be removed, same-request cache invalidation is covered, and the mobile editor restores focus while the Arabic queue uses translated module and field labels.
- Dashboard is a complete role-owned slice. Its former 405-line presenter is a 23-line role selector; the operations and tenant React views are 34-line and 28-line composers over focused modules. Arrears are calculated with a scoped database aggregate, all-lease payloads are gone, CMS status is superadmin-only, tenant payments are posted-only, Arabic document titles reach the portal, and dedicated architecture/feature tests protect the split.
- Shared frontend infrastructure is modular. The former 1,047-line resource cycle and 660-line data table are now one-line compatibility barrels backed by focused modules for forms, fields, actions, detail tabs, documents, history, query state, toolbars, desktop tables, mobile cards, and pagination. Every extracted unit is capped by architecture tests; existing feature imports and route payload contracts remain unchanged.

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

Modularize the admin shell next: split navigation, drawer state, topbar search, account controls, and responsive styles while preserving role permissions, RTL, keyboard focus, and the 64px mobile contract. Then continue production onboarding work for SMTP, backup restore, legal templates, and a controlled pilot property.
