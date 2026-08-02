# MVP Readiness Audit

Updated: August 2, 2026

## Decision

The product is an operational MVP release candidate. It does not need another broad redesign. It needs controlled production onboarding, business-rule approval, and recovery/communication infrastructure before real tenant rollout.

## What works end to end

- Superadmin creates a portfolio, assigns owners/managers, creates the asset hierarchy, and controls modules, CMS, wording, reports, audit, media, and showcase data.
- Owners create and assign portfolio records. Managers perform tenant, lease, payment, PDF, expense, and maintenance work only inside owner-assigned properties/buildings and their descendants; an unassigned manager has no operational access.
- Owners and superadmins can import a real portfolio's opening assets, tenants, active/draft leases, installments, and payments from one controlled bilingual XLSX workbook. The private preview writes nothing, validates references and conflicts, expires automatically, and commits through existing domain actions in one all-or-nothing transaction.
- Tenants see only their own lease, posted payments, public PDF documents, contract balance/days remaining, and maintenance requests.
- Owners and assigned managers can open a focused Portal Access page from a user or tenant record, generate a fresh 60-minute password-setup link, and share it privately without retaining or exposing a temporary password. New links revoke older reset links, inactive accounts are blocked, and generation is audited without storing the token.
- Active or expired leases can create one linked renewal draft. Renewal dates, source lineage, status, and occupancy activation are guarded at the action layer.
- Active or expired leases use a controlled move-out plan instead of direct termination. The handover requires returned keys, a deposit decision, termination and inspection PDFs, and a reached move-out date; completion snapshots remaining debt, releases occupancy, and remains visible in lease history.
- Owners and managers have a durable collection-control cycle for every open installment: assign the account, record each contact, capture promises, schedule the next action, identify missed promises, and keep the history visible from the collection queue, lease, dashboard, reports, and XLSX export.
- Owners and managers start each day in one prioritized Action Center rather than checking four separate modules. Collections, maintenance, renewals, and move-outs retain their original scoped source records and direct action pages; the combined queue adds property, responsible-person, priority, type, pagination, and XLSX controls without duplicating workflow state.
- Owners and managers compare every accessible property in one card-based Portfolio Control workspace, rank pressure by arrears, occupancy, collection, or cash flow, and continue directly into the selected property dashboard, Action Center, or reports.
- Owners and managers open a dedicated Operating Report for one authorized top-level property, keep its hierarchy, ownership, occupancy, period finances, maintenance, and source queues in context, and download the same scope as PDF, DOCX, or XLSX.
- Owners and managers manage reusable report scopes in a dedicated card workspace, with focused create/edit screens, actor-owned duplication, fixed or rolling periods, and real XLSX downloads using the identical property and date scope.
- Owners and managers open a dedicated Account Statement from one authorized tenant record, review all scoped contracts plus period installments, payments, documents, and maintenance in focused tabs, and download the same bounded ledger as PDF, DOCX, or XLSX. Currency totals remain separate, and assigned managers never receive records from an unassigned property.
- Lease, payment, maintenance, and expense details expose a role-aware Next Step panel instead of hiding lifecycle actions across edit forms and index menus.
- Maintenance stays accountable after management marks work resolved: tenant sign-off or reopen actions, service-report PDFs, and a shared “awaiting tenant confirmation” queue/filter keep unresolved handoffs visible to both sides and to scoped XLSX exports.
- Superadmins can export the selected Launch Readiness decision as localized PDF, DOCX, or a four-sheet XLSX audit pack. It carries the measured infrastructure checks, recorded evidence, confirming actor and timestamp, portfolio metrics, blockers, and owner approvals under the same scope shown on screen.
- Public pages, authentication, admin, forms, tables, reports, documentation, CMS, map, validation, and statuses support English and Arabic with RTL rendering.

## UI and scale evidence

- Playwright and axe cover 64 scenarios at 390, 768, 1024, and 1440 pixels. Primary routes, Opening Data, Launch Readiness report controls, every Property Operating Report tab, and the tenant Account Statement have no page-level horizontal overflow. Browser titles use the localized product name instead of framework starter branding, the operations dashboard uses focused URL-backed workspaces instead of a full-page section wall, and the tenant command center keeps its payment, maintenance, and contract panels fully translated in Arabic.
- Desktop resource indexes use bounded server-side tables; below 992 pixels they switch to compact record cards. Detail pages become one column below 1200 pixels and split long content into query-backed tabs.
- The local stress database contains 861 assets, 484 tenant profiles, 486 leases, 1,611 payments, 126 collection follow-ups, 330 maintenance requests, 250 expenses, 972 documents, and 15,625 audit events.
- Table tests cover 10, 25, 50, and 100 records per page, search, filtering, pagination, portfolio isolation, Arabic query state, and scoped XLSX exports.
- The main CSS bundle is 315.11 KB before gzip, below the 325 KB release ceiling. Opening Data, Property Operating Reports, Action Center, Map, and other heavy route styles/scripts remain lazy chunks.
- The Playwright PHP server now runs with a 1 GB test memory limit; the previous 128 MB long-suite process accumulated memory and died during the repeated route sweep.

## Data and security integrity

- Portfolio scoping, assigned-property manager scoping, and tenant isolation are enforced in queries, requests, actions, global search, exports, documents, dashboards, reports, map payloads, and detail presenters. Assignment to a property/building includes its descendants; an unassigned manager gets no operational scope.
- Financial writes use database transactions, allocation locks, reversible void flows, and lease-derived portfolio/tenant/currency authority.
- Opening-data imports are actor-bound, portfolio-scoped, size-limited, conflict-checked, and atomic. A forced payment-write failure is covered by a regression test proving that assets, tenants, leases, installments, payments, and allocations all roll back.
- Signed uploads require a genuine PDF signature. Contracts, statements, receipts, and tenant-visible files use authorized private downloads.
- Reports and exports are real XLSX workbooks, not renamed CSV files.
- Tenant Account Statement totals are calculated from the complete authorized dataset even when the browser ledger reaches its 100-row safety limit; mixed SAR/USD history is never collapsed into one fake total.
- Move-out evidence remains PDF-only, move-out exports are real XLSX workbooks, and direct active/expired lease deletion cannot bypass the handover guard.
- Maintenance states are guarded: open/in-progress work may resolve or cancel; resolved/cancelled work must reopen before continuing.
- Activity history covers operational state changes without exposing secrets or private server paths.
- Composer and pnpm report no known dependency vulnerabilities. Dompdf 3.1.6 and PostCSS 8.5.18 include the July 22 security fixes, and PHPStan adds zero findings outside the accepted legacy baseline.

## Required before real users

These are enforced as auditable launch gates in the superadmin-only `/system/readiness` workspace. Automatic checks refresh from live system state; manual approvals require notes and record the confirming actor and time.

1. Configure production SMTP and prove password-reset delivery to a real mailbox.
2. Confirm the one-minute scheduler cron is active and that queued jobs drain without failed jobs.
3. Create and download a package from `/system/backups`, place it in protected offsite storage, then complete one documented restore drill. An untested backup is just optimism with a filename.
4. Have the property owner or legal adviser approve the English and Arabic lease clauses, renewal wording, termination wording, and receipt template.
5. Use `/opening-data` to import one real portfolio, then reconcile its opening balances, deposits, active leases, unit occupancy, currencies, and due dates against the source records.
6. Purge or clearly isolate showcase data before real KPIs are used for operating decisions.
7. Run a controlled acceptance pilot with one superadmin, owner, manager, and tenant using real devices and one real maintenance/payment cycle.

The workspace also evaluates each portfolio for an active owner, manager coverage, tenant access, property inventory, assignment gaps, bilingual current lease terms, and showcase contamination. A green server does not make an unprepared portfolio operational.

## Deliberate MVP limits

- Payments are entered manually. There is no gateway, bank feed, or reconciliation engine.
- Signing is PDF upload/download, not cryptographic e-signature.
- Finance is operational reporting, not double-entry accounting or tax filing.
- Email is queue-ready and the scoped in-app notification inbox is operational. Production SMTP still needs proof; SMS and WhatsApp integrations remain outside the MVP.
- Maintenance has internal workflow and expenses, but no vendor portal, procurement, SLA escalation, or inventory of spare parts.

## Next delivery goal

Clear `/system/readiness`, make one real portfolio operational for 30 days, record every pilot defect, then build only what that evidence proves is missing. Another visual overhaul now would be motion without progress.
