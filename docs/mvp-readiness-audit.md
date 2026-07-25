# MVP Readiness Audit

Updated: July 25, 2026

## Decision

The product is an operational MVP release candidate. It does not need another broad redesign. It needs controlled production onboarding, business-rule approval, and recovery/communication infrastructure before real tenant rollout.

## What works end to end

- Superadmin creates a portfolio, assigns owners/managers, creates the asset hierarchy, and controls modules, CMS, wording, reports, audit, media, and showcase data.
- Owners create and assign portfolio records. Managers perform tenant, lease, payment, PDF, expense, and maintenance work only inside owner-assigned properties/buildings and their descendants; an unassigned manager has no operational access.
- Tenants see only their own lease, posted payments, public PDF documents, contract balance/days remaining, and maintenance requests.
- Active or expired leases can create one linked renewal draft. Renewal dates, source lineage, status, and occupancy activation are guarded at the action layer.
- Active or expired leases use a controlled move-out plan instead of direct termination. The handover requires returned keys, a deposit decision, termination and inspection PDFs, and a reached move-out date; completion snapshots remaining debt, releases occupancy, and remains visible in lease history.
- Lease, payment, maintenance, and expense details expose a role-aware Next Step panel instead of hiding lifecycle actions across edit forms and index menus.
- Public pages, authentication, admin, forms, tables, reports, documentation, CMS, map, validation, and statuses support English and Arabic with RTL rendering.

## UI and scale evidence

- Playwright and axe cover 44 scenarios at 390, 768, 1024, and 1440 pixels. Primary routes have no page-level horizontal overflow.
- Desktop resource indexes use bounded server-side tables; below 992 pixels they switch to compact record cards. Detail pages become one column below 1200 pixels and split long content into query-backed tabs.
- The local stress database contains 861 assets, 484 tenant profiles, 486 leases, 1,611 payments, 330 maintenance requests, 250 expenses, 972 documents, and 15,282 audit events.
- Table tests cover 10, 25, 50, and 100 records per page, search, filtering, pagination, portfolio isolation, Arabic query state, and scoped XLSX exports.
- The main CSS bundle is 318.37 KB before gzip, below the 325 KB release ceiling. Map and other heavy route styles/scripts remain lazy chunks.
- The Playwright PHP server now runs with a 1 GB test memory limit; the previous 128 MB long-suite process accumulated memory and died during the repeated route sweep.

## Data and security integrity

- Portfolio scoping, assigned-property manager scoping, and tenant isolation are enforced in queries, requests, actions, global search, exports, documents, dashboards, reports, map payloads, and detail presenters. Assignment to a property/building includes its descendants; an unassigned manager gets no operational scope.
- Financial writes use database transactions, allocation locks, reversible void flows, and lease-derived portfolio/tenant/currency authority.
- Signed uploads require a genuine PDF signature. Contracts, statements, receipts, and tenant-visible files use authorized private downloads.
- Reports and exports are real XLSX workbooks, not renamed CSV files.
- Move-out evidence remains PDF-only, move-out exports are real XLSX workbooks, and direct active/expired lease deletion cannot bypass the handover guard.
- Maintenance states are guarded: open/in-progress work may resolve or cancel; resolved/cancelled work must reopen before continuing.
- Activity history covers operational state changes without exposing secrets or private server paths.
- Composer and pnpm report no known dependency vulnerabilities. Dompdf 3.1.6 and PostCSS 8.5.18 include the July 22 security fixes, and PHPStan adds zero findings outside the accepted legacy baseline.

## Required before real users

These are enforced as auditable launch gates in the superadmin-only `/system/readiness` workspace. Automatic checks refresh from live system state; manual approvals require notes and record the confirming actor and time.

1. Configure production SMTP and prove password-reset delivery to a real mailbox.
2. Confirm the one-minute scheduler cron is active and that queued jobs drain without failed jobs.
3. Back up MySQL and private document storage, then complete one documented restore drill. An untested backup is just optimism with a filename.
4. Have the property owner or legal adviser approve the English and Arabic lease clauses, renewal wording, termination wording, and receipt template.
5. Import and reconcile one real portfolio's opening balances, deposits, active leases, unit occupancy, currencies, and due dates.
6. Purge or clearly isolate showcase data before real KPIs are used for operating decisions.
7. Run a controlled acceptance pilot with one superadmin, owner, manager, and tenant using real devices and one real maintenance/payment cycle.

The workspace also evaluates each portfolio for an active owner, manager coverage, tenant access, property inventory, assignment gaps, bilingual current lease terms, and showcase contamination. A green server does not make an unprepared portfolio operational.

## Deliberate MVP limits

- Payments are entered manually. There is no gateway, bank feed, or reconciliation engine.
- Signing is PDF upload/download, not cryptographic e-signature.
- Finance is operational reporting, not double-entry accounting or tax filing.
- Email is queue-ready, but there is no notification inbox, SMS, or WhatsApp integration.
- Maintenance has internal workflow and expenses, but no vendor portal, procurement, SLA escalation, or inventory of spare parts.

## Next delivery goal

Clear `/system/readiness`, make one real portfolio operational for 30 days, record every pilot defect, then build only what that evidence proves is missing. Another visual overhaul now would be motion without progress.
