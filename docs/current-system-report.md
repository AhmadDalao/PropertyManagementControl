# SYSTEM REPORT: Property Management Control Functional Specification

**Report date:** August 11, 2026  
**Application build revision:** `fecf276fc0c31f9e4e2038e7db09c10acdcaac04`
**Production URL:** `https://property.ahmaddalao.com`  
**Assessment:** Operational MVP release candidate; not yet approved for an unattended real-property launch.

## 1. Executive Status

The repository contains a broad, working property-operations platform rather than a prototype. It covers portfolio and property setup, a hierarchical property/unit model, owner and manager assignments, tenant onboarding, leases, installment schedules, manual payment allocation, collection follow-up, expenses, maintenance requests, contractors and work orders, PDF records, reporting, CMS, bilingual wording, audit history, backups, and launch-readiness controls.

The application code is healthy. The release reran the complete PHP suite: **653 tests and 37,915 assertions passed**. The release baseline also includes 70 passing Playwright/axe scenarios, TypeScript, ESLint, Prettier, Pint, Vite, route, migration, and touched-module PHPStan checks.

The verified release is deployed to Hostinger and matches GitHub. The live site responds successfully at `/up`, `/system/settings` is active, a pre-migration backup completed, and authenticated EN/AR dashboard, reports, CMS, PDF, and XLSX smoke checks passed. SMTP, the one-minute Hostinger scheduler, a reconciled real opening-data import, approved legal wording, and the four-role pilot remain launch blockers.

### Current verdict

| Area | State | Comment |
|---|---|---|
| Core domain workflows | Ready for pilot | Property through payment and maintenance workflows exist and are tested. |
| Authorization | Strong, complex | Role, portfolio, module, and assigned-property scopes are enforced and heavily tested. |
| Responsive UI | Release baseline passes | Desktop tables and mobile cards are implemented; automated widths cover 390/768/1024/1440. |
| English/Arabic | Implemented | UI dictionaries, RTL, bilingual records, PDFs, DOCX, XLSX, CMS, and wording overrides exist. |
| Reporting | Operational | Real `.xlsx`, PDF, and DOCX outputs exist; unlike currencies remain separated. |
| Email | Blocked in production | Live mailer was last recorded as `log`; SMTP receipt is unproven. |
| Scheduler/queue | Blocked in production | Code is ready, but no three-heartbeat production evidence exists. |
| Real portfolio data | Not started | Production has showcase data but no approved live portfolio import. |
| Legal/compliance approval | Owner action required | Lease wording, billing rules, retention, and opening balances need business/legal sign-off. |
| Latest deployment | Current | GitHub and Hostinger run the verified release; the production manifest matches the local build. |

## 2. Audit Scope And Method

This report was built from the current source, route registry, request validation, access classes, presenters, React modules, model relationships, scheduler, package manifests, existing release evidence, and a fresh test run. It distinguishes three things:

- **Implemented:** present in source and covered by the current application architecture.
- **Production active:** verified on the live Hostinger deployment.
- **Operationally approved:** proven with real users, reconciled real data, and business evidence.

Those states are not interchangeable. The code can be complete while the deployment is still not safe to call live-ready.

### Functional-specification map

This document is both a release assessment and the functional specification for the implemented product. Use the sections below as follows:

| Question | Authoritative section |
|---|---|
| Framework, frontend, backend, database, authentication | Section 3 |
| Roles, permissions, portfolio/property scope | Sections 4 and 20 |
| Sidebar, account menu, global search, property context | Section 5 |
| Every browser page and route family | Sections 6 and 19 |
| Dashboard KPIs, widgets, filters, actions, sources | Section 7 |
| Shared table behavior, search, pagination, export, bulk actions | Section 8 |
| Properties, units, tenants, leases, payments, expenses, maintenance, documents, reports, CMS, and system controls | Section 9 |
| Statuses and lifecycle transitions | Section 10 |
| PDF, DOCX, XLSX, and register exports | Section 11 |
| Email, WhatsApp, maps, storage, queue, scheduler, external APIs | Section 12 |
| Database models and relationships | Section 13 |
| React pages, shared components, and design system | Sections 14 and 21 |
| Existing problems, missing features, and launch blockers | Sections 15 and 22 |
| Exact page contracts and non-CRUD forms | Sections 19 and 20 |

Implementation labels used in this report:

- **Implemented:** the route, authorization, server payload, and UI exist in the current source.
- **Operational dependency:** code exists, but production needs external configuration or evidence.
- **Not implemented:** the capability does not exist and must not be promised to users.

## 3. Architecture

| Layer | Technology and design |
|---|---|
| Framework | Laravel `13.17`, PHP `^8.4.1`, server-rendered Laravel entry with Inertia responses. |
| Frontend | React `19.2`, TypeScript `5.7`, Inertia React `3`, Bootstrap `5.3.8`, Bootstrap Icons, custom modular CSS, Vite `8.1.3`. |
| Production rendering | Laravel monolith. Vite builds static browser assets; no Node server is required in production. |
| Backend organization | Thin HTTP controllers over vertical modules in `app/Modules/*`: Actions, Queries, Presenters, Requests, Support, Jobs, and Data objects. |
| Frontend organization | Thin Inertia page entries under `resources/js/pages/*`; feature UI under `resources/js/modules/*`; shared data-table, operations, resource-cycle, shell, and translation components. |
| Database | MySQL in production. Local audit environment currently uses SQLite. Eloquent models and migrations are the schema source. |
| Authentication | Laravel session authentication, password reset tokens, remember-me, login throttling, active-account enforcement, and forced permanent-password change. |
| Authorization | Spatie Laravel Permission plus feature access classes, portfolio scoping, portfolio module toggles, and manager assigned-property scoping. |
| Session/cache/queue | Database-backed on shared hosting. Queue workers are short-lived and invoked by the scheduler. No Redis, Horizon, or Supervisor is required. |
| Files | Private local storage for contracts, documents, evidence, backups, and reports; public storage for approved media. Shared-hosting storage mirror command exists. |
| Documents | Dompdf with Arabic shaping for PDFs; internal OOXML builders for real DOCX and XLSX packages. |
| Mapping | Leaflet `1.9.4`, Leaflet MarkerCluster `1.5.3`, configurable OpenStreetMap tile URL and attribution. |
| Activity history | Spatie Activitylog plus explicit operational timelines and immutable collection follow-ups. |
| Localization | Laravel `en`/`ar` dictionaries, typed React translator, `ar-SA` formatting, RTL document direction, CMS bilingual content, and database wording overrides. |

### Main source boundaries

- `app/Modules/*`: backend business modules.
- `app/Models/*`: 31 application models.
- `resources/js/modules/*`: frontend feature modules.
- `resources/js/components/data-table/*`: responsive list system.
- `resources/js/components/resource-cycle/*`: create, edit, detail, document, related-record, and history primitives.
- `resources/js/modules/shell/*`: sidebar, topbar, account, global search, and property context.
- `resources/css/styles/*`: modular visual layers; 20,923 source CSS lines across route and component styles.
- `routes/web.php`: 237 registered non-vendor endpoints.
- `routes/console.php`: lifecycle synchronization, queue, reports, heartbeat, and backup schedules.

## 4. Roles, Scope, And Permissions

There is no generic `admin` role. The real roles are `superadmin`, `owner`, `property_manager`, and `tenant`.

### Role capabilities

| Capability | Superadmin | Owner | Property manager | Tenant |
|---|---:|---:|---:|---:|
| Platform dashboard | Global | Own portfolio | Assigned properties | Own tenancy |
| Create portfolios | Yes | No | No | No |
| Edit portfolio | Any | Own portfolio | No | No |
| Archive portfolio | Yes | No | No | No |
| Create/manage users | Any role | Managers and tenants | Assigned tenants only | No |
| Assign manager properties | Yes | Own portfolio | No | No |
| Assets/property hierarchy | All | Own portfolio | Assigned roots and descendants | No |
| Tenant profiles | All | Own portfolio | Assigned-property tenants | Own profile through portal only |
| Leases | All | Own portfolio | Assigned-property leases | View own leases only |
| Payments | All | Own portfolio | Assigned-property payments | View own posted payments/receipts |
| Rent follow-up | All | Own portfolio | Assigned-property installments | No |
| Maintenance | All | Own portfolio | Assigned-property requests | Create/view/comment on own requests |
| Vendors/work orders | All | Own portfolio | Assigned-property requests | No |
| Expenses | All | Own portfolio | Assigned-property expenses | No |
| Documents | All | Own portfolio | Assigned records | Public portal-safe own lease/payment PDFs only |
| Reports | Global | Own portfolio | Assigned properties | Own lease/payment views only |
| CMS/public website | Yes | No | No | No |
| Wording overrides | Yes | No | No | No |
| Readiness/settings/backups/data lab | Yes | No | No | No |
| Audit history | Global | Own portfolio | Assigned scope | No |

### Effective access layers

1. **Account gate:** authenticated, status `active`, and permanent password established.
2. **Role gate:** feature access classes restrict major capabilities.
3. **Portfolio gate:** non-superadmins are limited to `users.portfolio_id`.
4. **Module gate:** each portfolio can enable/disable `users`, `assets`, `tenants`, `leases`, `payments`, `maintenance`, `expenses`, `reports`, `documents`, and `media`.
5. **Property gate:** a property manager sees only assigned root properties and descendants. Related tenants, leases, payments, expenses, documents, and maintenance are derived from that asset scope.
6. **Tenant gate:** tenants see only records tied to their own tenant profile; internal notes, audit data, private files, contractors, costs, and other tenants are removed server-side.

### Spatie permission names

`view dashboard`, `manage portfolios`, `manage users`, `manage assets`, `manage tenants`, `manage leases`, `manage payments`, `manage maintenance`, `manage expenses`, `view reports`, `manage cms`, `manage media`, and `download documents`.

The feature access classes are the authoritative runtime boundary. The Spatie matrix is not sufficient by itself; for example, manager user-management behavior is more specific than its coarse permission list.

## 5. Sidebar And Navigation

The sidebar is role-aware, module-aware, and property-context-aware. It supports a 272px expanded desktop state, a 76px collapsed state, persisted preference, and a mobile drawer.

### Tenant Portal

Tenants receive a deliberately smaller navigation instead of the management directory: Home (`/dashboard`), My Lease (`/my-lease`), Payments (`/my-payments`), Maintenance Requests (`/maintenance-requests`), Documents (`/my-documents`), and Profile (`/profile`). Notifications remain available from the topbar/account menu, and contextual help links open the role-filtered documentation library.

### Overview

| Item | Path | Access |
|---|---|---|
| Dashboard | `/dashboard` | All roles; role-specific payload |
| Notifications | `/notifications` | Superadmin, owner, manager in sidebar; tenant through topbar/account menu |
| Company Control | `/company-control` | Superadmin |
| Portfolio Control | `/portfolio-control` | Superadmin, owner, manager; assets module |
| Action Center | `/action-center` | Superadmin, owner, manager |
| Properties Map | `/property-map` | Superadmin, owner, manager; assets module |
| Reports | `/reports` | Superadmin, owner, manager; reports module |

### Portfolio

| Item | Path | Access |
|---|---|---|
| Portfolios | `/portfolios` | Superadmin, owner, manager; visibility differs |
| Opening Data | `/opening-data` | Superadmin, owner |
| Property Explorer | `/property-explorer` | Superadmin, owner, manager; assets module |
| Tenants | `/tenants` | Superadmin, owner, manager; tenants module |
| Leases | `/leases` | Superadmin, owner, manager; leases module |
| Lease Renewals | `/lease-renewals` | Superadmin, owner, manager; leases module |
| Move-outs | `/lease-move-outs` | Superadmin, owner, manager; leases module |

### Money And Service

| Item | Path | Access |
|---|---|---|
| Rent Collection | `/rent-collection` | Superadmin, owner, manager; payments module |
| Payments | `/payments` | Superadmin, owner, manager; payments module |
| Expenses | `/expenses` | Superadmin, owner, manager; expenses module |
| Maintenance | `/maintenance-requests` | Management roles; tenant uses the Tenant Portal group |
| Work Orders | `/maintenance-work-orders` | Superadmin, owner, manager; maintenance module |
| Maintenance Contractors | `/maintenance-vendors` | Superadmin, owner, manager; maintenance module |
| Documents | `/documents` | Superadmin, owner, manager; documents module |

### System

| Item | Path | Access |
|---|---|---|
| Users | `/users` | Superadmin, owner, manager; users module |
| Website Control | `/cms` | Superadmin |
| Page Wording | `/wording` | Superadmin |
| Data Lab | `/system/showcase-data` | Superadmin |
| Launch Readiness | `/system/readiness` | Superadmin |
| Infrastructure Settings | `/system/settings` | Superadmin; deployed and active |
| Email Delivery | `/system/email-delivery` | Superadmin |
| Backup Control | `/system/backups` | Superadmin |
| Media | `/media-files` | Superadmin, owner, manager; media module |
| Audit | `/audit-logs` | Superadmin, owner, manager |
| Documentation | `/documentation` | Management roles in sidebar; all roles through contextual/account links, guides filtered by role/module |

The account menu adds Profile, EN/AR language switching, and Logout. The shell also contains global search and the property-context picker for management roles.

## 6. Route And Page Catalog

Access abbreviations: **SA** superadmin, **O** owner, **M** property manager, **T** tenant, **Public** anonymous or authenticated. Non-superadmin pages remain portfolio/module/property scoped even when the table says they can enter the module.

### Public and account pages

| Route | Page/purpose | Access |
|---|---|---|
| `/` | CMS-rendered homepage | Public |
| `/pages/{slug}` | Published CMS content page | Public |
| `/login` | Portal sign-in | Guest |
| `/account-recovery` | Request password-reset email | Guest |
| `/reset-password/{token}` | Establish/reset password | Guest with valid token |
| `/profile` | Profile and password settings | SA/O/M/T |
| `/notifications` | Operational notification inbox | SA/O/M/T |
| `/dashboard` | Role-specific command center | SA/O/M/T |
| `/my-lease` | Tenant-owned lease selector, contract summary, installment schedule, and authorized contract/statement downloads | T |
| `/my-payments` | Tenant-owned manual-payment history, balances, filters, pagination, and posted receipts | T |
| `/my-documents` | Tenant-safe lease, receipt, and statement document library | T |
| `/documentation` | Searchable guide library | SA/O/M/T |
| `/documentation/{guide}` | Individual bilingual guide | Role/module filtered |

### Platform and portfolio control pages

| Route | Page/purpose | Access |
|---|---|---|
| `/company-control` | Cross-portfolio platform cards and composition | SA |
| `/portfolio-control` | Property performance card grid | SA/O/M |
| `/action-center` | Unified collections, maintenance, renewal, move-out, document queue | SA/O/M |
| `/property-map` | Geographic clustered property map and directory | SA/O/M |
| `/property-explorer` | One-property hierarchy and operating context | SA/O/M |
| `/opening-data` | Preview and commit a real opening-data XLSX | SA/O |
| `/portfolios` | Portfolio directory | SA/O/M |
| `/portfolios/create` | Create client portfolio | SA |
| `/portfolios/{portfolio}` | Portfolio setup, finance, related records, documents, history | SA/O/M scoped |
| `/portfolios/{portfolio}/edit` | Edit portfolio/module controls | SA or owning O |

### Properties and units

| Route | Page/purpose | Access |
|---|---|---|
| `/assets` | Property/building/floor/unit/space directory | SA/O/M |
| `/assets/create` | Create one hierarchy record | SA/O/M with assignment |
| `/assets/building-setup` | Create building, floors, and units in one transaction | SA/O |
| `/assets/{asset}` | Asset operating detail | SA/O/M scoped |
| `/assets/{asset}/edit` | Edit asset, ownership, manager, map, valuation | SA/O/M scoped |

### Users and tenants

| Route | Page/purpose | Access |
|---|---|---|
| `/users` | Access-account directory | SA/O/M |
| `/users/create` | Create role account | SA/O/M, assignable roles constrained |
| `/users/{user}` | Account, role, assignments, workload, documents, history | SA/O/M scoped |
| `/users/{user}/edit` | Edit manageable account | SA/O/M scoped |
| `/users/{user}/property-assignments` | Assign manager property roots | SA/O |
| `/portal-accounts/{user}/access` | Portal handoff and setup-link status | SA/O/M scoped |
| `/tenants` | Tenant-profile directory | SA/O/M |
| `/tenants/create` | Create tenant account/profile, optionally continue to lease | SA/O/M scoped |
| `/tenants/{tenant}` | Tenant profile, rental, balance, service, docs, history | SA/O/M scoped |
| `/tenants/{tenant}/edit` | Edit tenant and account state | SA/O/M scoped |
| `/tenants/{tenant}/account-statement` | Full tenant account statement | SA/O/M scoped |

### Leases, collection, and payments

| Route | Page/purpose | Access |
|---|---|---|
| `/leases` | Lease directory | SA/O/M |
| `/leases/create` | Create contract and installment schedule | SA/O/M scoped |
| `/leases/{lease}` | Contract, financial, docs, related records, history | SA/O/M scoped; T own lease |
| `/leases/{lease}/edit` | Edit status, sign date, notice, notes, terms | SA/O/M scoped |
| `/leases/{lease}/renew` | Prefilled renewal contract form | SA/O/M scoped |
| `/leases/{lease}/move-out` | Move-out planning form | SA/O/M scoped |
| `/leases/{lease}/statement` | Tenant lease statement PDF | SA/O/M scoped; T own lease |
| `/lease-renewals` | Renewal/expiry queue | SA/O/M |
| `/lease-move-outs` | Move-out queue | SA/O/M |
| `/rent-collection` | Installment collection register | SA/O/M |
| `/rent-collection/{installment}/follow-up` | Append-only contact and promise history | SA/O/M scoped |
| `/payments` | Payment register | SA/O/M |
| `/payments/create` | Post manual payment | SA/O/M scoped |
| `/payments/{payment}` | Receipt, allocation, tenant/lease, audit | SA/O/M scoped; T own payment |
| `/payments/{payment}/edit` | Edit mutable payment fields/status | SA/O/M scoped |

### Maintenance and expenses

| Route | Page/purpose | Access |
|---|---|---|
| `/maintenance-requests` | Service request queue | SA/O/M/T |
| `/maintenance-requests/create` | Open request and attach evidence | SA/O/M/T scoped |
| `/maintenance-requests/{request}` | Triage, updates, work, cost, evidence, sign-off | SA/O/M scoped; T own request |
| `/maintenance-requests/{request}/edit` | Management triage/comment form | SA/O/M scoped |
| `/maintenance-requests/{request}/attachments/create` | Add private image evidence | SA/O/M/T scoped |
| `/maintenance-requests/{request}/resolution-response` | Tenant confirm or reopen resolved issue | Owning T |
| `/maintenance-requests/{request}/work-orders/create` | Create contractor work order | SA/O/M scoped |
| `/maintenance-work-orders` | Central work-order register | SA/O/M |
| `/maintenance-work-orders/{order}` | Assignment, schedule, scope, costs, completion | SA/O/M scoped |
| `/maintenance-work-orders/{order}/edit` | Update work-order workflow | SA/O/M scoped |
| `/maintenance-vendors` | Contractor directory | SA/O/M |
| `/maintenance-vendors/create` | Add contractor | SA/O/M scoped |
| `/maintenance-vendors/{vendor}` | Contractor profile and related work | SA/O/M scoped |
| `/maintenance-vendors/{vendor}/edit` | Edit contractor | SA/O/M scoped |
| `/expenses` | Expense register | SA/O/M |
| `/expenses/create` | Record expense | SA/O/M scoped |
| `/expenses/{expense}` | Cost source, amount, workflow, history | SA/O/M scoped |
| `/expenses/{expense}/edit` | Edit mutable expense | SA/O/M scoped |

### Documents, media, audit, and search

| Route | Page/purpose | Access |
|---|---|---|
| `/documents` | Private PDF register | SA/O/M |
| `/documents/create` | Attach PDF to asset, lease, or payment | SA/O/M scoped |
| `/documents/{document}` | Metadata, validity, attached record, access, history | SA/O/M scoped |
| `/documents/{document}/edit` | Edit metadata/visibility | SA/O/M scoped |
| `/media-files` | CMS/portfolio image library | SA/O/M |
| `/media-files/create` | Upload image | SA/O/M |
| `/media-files/{mediaFile}` | Preview, metadata, dimensions, history | SA/O/M scoped |
| `/media-files/{mediaFile}/edit` | Edit media metadata/access | SA/O/M scoped |
| `/audit-logs` | Scoped activity directory | SA/O/M |
| `/global-search?q=` | Grouped exact/fuzzy operational search | SA/O/M/T, role scoped |
| `/search?q=` | Full grouped search-results page using the same role-scoped search sources | SA/O/M/T, role scoped |

### Reports

| Route | Page/purpose | Access |
|---|---|---|
| `/reports` | Report library and command center | SA/O/M |
| `/reports/statement` | Owner/portfolio statement | SA/O/M scoped |
| `/reports/properties/{asset}` | One-property operating report | SA/O/M scoped |
| `/reports/rent-roll` | Rentable-asset rent roll | SA/O/M scoped |
| `/reports/arrears-aging` | Overdue installment aging | SA/O/M scoped |
| `/reports/saved` | Saved report directory | SA/O/M |
| `/reports/saved/create` | Create reusable report scope | SA/O/M |
| `/reports/saved/{preset}` | Saved report detail and outputs | Visibility/ownership scoped |
| `/reports/saved/{preset}/edit` | Edit owned preset | Owner or SA; author rules apply |
| `/reports/daily-operations` | Immutable daily operations archive | SA/O |
| `/reports/daily-operations/{run}` | Archived report detail/downloads | SA/O scoped |

### CMS and system control

| Route | Page/purpose | Access |
|---|---|---|
| `/cms` | Pages, reusable sections, and navigation workspace | SA |
| `/cms/pages/create` | Create CMS page | SA |
| `/cms/pages/{page}` | Visual section builder and preview | SA |
| `/cms/pages/{page}/edit` | Page identity, SEO, visibility, status | SA |
| `/cms/sections/create` | Create reusable section | SA |
| `/cms/sections/{section}/edit` | Edit reusable bilingual section | SA |
| `/cms/navigation/create` | Create header/footer link | SA |
| `/cms/navigation/{item}/edit` | Edit navigation item | SA |
| `/wording` | Search/edit/reset EN/AR interface copy and translation queue | SA |
| `/system/showcase-data` | Generate/retry/purge tagged stress dataset | SA |
| `/system/readiness` | Automatic and evidence launch checks | SA |
| `/system/settings` | SMTP draft and scheduler command | SA; not live yet |
| `/system/email-delivery` | Email delivery/failure register | SA |
| `/system/email-delivery/{log}` | Delivery attempt detail | SA |
| `/system/backups` | Create/download/prune recovery packages | SA |

### Non-page workflow and download routes

All create/update/archive operations use POST, PUT/PATCH, or DELETE on the resource paths above. Additional endpoints include:

- Locale change: `POST /locale/{locale}`; logout: `POST /logout`.
- Notifications: `POST /notifications/read-all`, `POST /notifications/{notification}/read`.
- Property creation: `POST /assets`, `POST /assets/building-setup`.
- Portal handoff: `POST /portal-accounts/{user}/access-link`.
- Manager assignments: `PUT /users/{user}/property-assignments`.
- Lease documents/workflows: PDF `/contract`, DOCX `/contract.docx`, signed PDF upload, renewal, move-out update/complete/delete, and statement PDF.
- Maintenance: attachment upload/view, triage update, resolve/reopen response, work-order creation/update, service-report PDF/DOCX.
- Receipts/files: `/payments/{payment}/receipt`, `/documents/{document}/download`, `/media-files/{media}/file`.
- Generic XLSX exports: `/exports/{resource}` for assets, tenants, leases, renewals, move-outs, payments, rent collection, maintenance, work orders, expenses, documents, users, portfolios, CMS pages, and media.
- Report outputs: PDF/DOCX/XLSX for statements, property reports, rent roll, arrears aging, daily operations, readiness, tenant statements, plus XLSX platform/audit/email exports.
- CMS: page/section/navigation writes, attach/detach, visibility/settings update, bilingual content update, and `PUT /cms/pages/{page}/sections/reorder`.
- Opening data: template download, preview, discard, and atomic import.
- Readiness: evidence update and throttled test email.
- Backups/showcase: queued create, verified download, prune, generate, retry, and typed-confirmation purge.

## 7. Dashboard

### Shared controls

- Global property context, stored in session and carried into dashboards, directories, reports, map, queues, and exports.
- Role-aware global search.
- Dashboard property focus and direct links to source records.
- Quick actions are generated from actual missing setup or operating risk, not static shortcut clutter.

### Superadmin command center

| Widget/KPI | Data source |
|---|---|
| Portfolio, user, property/asset, and tenant composition | `portfolios`, `users`, `assets`, `tenant_profiles`; showcase/live separated |
| Total valuation | Asset valuations grouped by currency |
| Revenue, expenses, and net position | Posted `payments` and posted `expense_entries`, currency separated |
| Scheduled due, paid, collection rate, arrears | `lease_installments` and `payment_allocations` |
| Occupancy | Rentable assets and current occupancy state |
| Active/expiring leases | `leases`, dates, statuses, renewal notice |
| Maintenance backlog | Open/in-progress `maintenance_requests` |
| Property performance grid | Per-property occupancy, collection, arrears, revenue, expense, maintenance |
| Collection queue | Open/overdue installments and latest follow-up state |
| Move-out queue | `lease_move_outs` and handover requirements |
| Platform activity | Bounded, deduplicated cross-portfolio audit/operation stream |
| CMS status | Pages, publish states, content readiness |
| Launch readiness | Automatic environment checks and evidence checklist |
| Setup checklist | First incomplete portfolio onboarding step |

### Owner and manager command center

- Portfolio/property valuation by currency.
- Rentable, occupied, vacant, and occupancy rate.
- Scheduled due, collected, collection rate, arrears, contract balance.
- Revenue, expenses, net position.
- Active/expiring contracts and move-outs.
- Open maintenance, urgent service, and tenant-confirmation backlog.
- Property performance cards, collection queue, recent payments, recent maintenance, next actions, and setup progress.
- Managers receive the same operating widgets only for assigned property roots and descendants.

### Tenant command center

- Current lease code and rented asset.
- Contract start/end and days remaining.
- Total paid, remaining contract balance, due now, and overdue amount.
- Next due installment.
- Contract and statement PDF downloads.
- Posted payment history with receipt links.
- Public, portal-safe lease/payment documents.
- Own maintenance queue and create-request action.

### Dashboard charts/visuals

- Occupancy composition.
- Property performance card/grid comparison.
- Revenue/expense and currency-position cards.
- Operational queues and health signals.
- Platform composition and activity for superadmin.

There is no external charting service. Data is calculated through scoped Laravel queries and rendered with custom React/Bootstrap components.

## 8. Shared List-Page Contract

All major operational indexes use the shared `DataTable`/`OperationsTable` and workspace pattern.

### Standard behavior

- Server-side search and filtering.
- Query-string state: `search`, `status`, `portfolio_id`, `property_id`, `per_page`, `page`, `sort`, `direction`, plus module filters.
- Page sizes: 10, 25, 50, 100; default 10.
- Sort whitelist per query; stable secondary sort by ID.
- Count chips and KPI summaries.
- Active-filter chips, reset, and filter drawer/panel.
- XLSX export using the same filter and authorization scope.
- Desktop table at large widths and compact record cards below 992px.
- One row action menu, clickable record, empty state, and create shortcut where allowed.
- No DataTables plugin and no unbounded browser-side dataset.

### Bulk actions

There are **no general multi-row bulk edit/delete actions**. This is deliberate and safer for leases, money, service, and audit records. Opening Data performs a validated atomic bulk import; showcase purge removes only tagged generated data; backup pruning targets controlled backup records.

### Delete semantics

Most destructive buttons are business-state changes rather than hard deletion:

- Assets and portfolios archive.
- Leases terminate.
- Payments void and reverse allocations.
- Expenses void/archive according to state.
- CMS records archive/remove from composition.
- Audit history is retained.

## 9. Module Catalog

Validation notation: `required`, `nullable`, `max:n`, `exists`, `unique`, and date/number ranges are server rules. Update forms use the same core rules unless a delta is stated.

### 9.1 Portfolios

**List columns:** portfolio identity/code/status; owner and location; operations counts; valuation/revenue/arrears/finance; enabled module access; actions.  
**Filters/search:** search name/code/email/phone/location; status; server pagination/sort.  
**Cards/counts:** all/active/inactive/archived, assets, users, active leases, maintenance, valuation and financial position.  
**Detail:** setup progress; portfolio status; valuation; posted revenue; net; business profile; ownership/modules; related properties/users/tenants/leases/payments/maintenance; documents; audit history.  
**Actions:** create, edit, archive, continue sequential setup, open scoped registers/reports.

**Form fields and rules:**

- `name_en`, `name_ar`: required, max 255.
- `code`: optional, max 50, ASCII alpha-dash, unique, normalized uppercase.
- `contact_email`: optional email; `contact_phone`: optional max 30.
- `city`: optional; `country`: required, default Saudi Arabia.
- `address`, `address_ar`: optional, max 2,000.
- `default_currency`: required uppercase ISO-style three letters.
- `status`: active or inactive on creation; archive is a workflow action.
- Ten boolean module controls: users, assets, tenants, leases, payments, maintenance, expenses, reports, documents, media.

### 9.2 Properties, Buildings, Floors, Units, And Spaces

The UI calls these properties/assets; the database uses one self-referential `assets` tree.

**List columns:** localized title/code; type/usage/parent; occupancy/status; primary owner/manager; valuation/currency/area; actions.  
**Filters:** status, asset type, usage type, occupancy, rentable yes/no, portfolio, property root, search, pagination, sort.  
**Detail tabs:** Overview; Financial; Documents; Related; History when data exists.  
**Detail content:** hierarchy and structure, ownership/management, valuation, location/map, occupancy health, collection health, arrears, service health, child/rentable assets, leases, collection queue, maintenance, expenses, documents, workflow decisions, audit timeline.  
**Actions:** create child, edit, archive, create tenant/lease/payment/expense/maintenance/document, open explorer/map/report.

**Single-record fields and rules:**

- Portfolio and parent asset, scoped and relationship checked.
- Type: property, building, floor, unit, space.
- Usage: residential, commercial, mixed, personal.
- Required bilingual title; optional unique code max 50.
- Status active/inactive; occupancy vacant/occupied/partially occupied/reserved/maintenance.
- Rentable boolean; valuation non-negative; three-letter currency; non-negative area.
- Level label, unit/space label.
- English/Arabic address and description.
- Zone legacy value plus English/Arabic zone, land number.
- Latitude -90..90, longitude -180..180; legacy schematic `map_x`/`map_y` 0..100 remains readable.
- Primary owner and primary manager user relationships.

**Building setup fields:** portfolio, bilingual building name, uppercase code prefix, usage, floor count, units per floor, floor start 0/1, unit or commercial-space type, required owner and manager, valuation, currency, building/unit area, bilingual address/zone, land number, latitude/longitude. It creates at most 250 validated records atomically.

### 9.3 Users And Property Assignments

**List columns:** name/email/phone; role; portfolio; portal access/account status/open workload; actions.  
**Filters:** status, role, portfolio for superadmin, search, pagination, sort.  
**Detail:** account identity/status/locale; role and portfolio; assigned properties; related stakeholder records and maintenance workload; documents; history.  
**Actions:** create, edit, deactivate/suspend, property assignments, portal-access handoff, open profile for self.

**Form fields:** portfolio, name, unique email, phone, preferred locale en/ar, active/inactive/suspended status, temporary password, role. Password uses Laravel's password defaults. Superadmin can assign all roles; owner can assign manager/tenant; manager can assign only a tenant in assigned scope. A user cannot manage their own role through the resource cycle.

**Property assignment form:** searchable top-level property roots with descendant scope. Only manager accounts are assignable; owners and superadmin control assignments.

### 9.4 Tenant Profiles

**List columns:** tenant identity/contact; individual/company profile; rental activity; profile status and portal-account status; actions.  
**Filters:** profile status, profile type, portfolio, property, search, pagination, sort.  
**Detail:** portal account, current rental, contract balance, open maintenance, profile/contact/emergency data, financial position, leases, payments, maintenance, documents, history.  
**Actions:** create, edit, block/inactivate, generate portal link, continue to prefilled lease, account statement.

**Fields:** portfolio; name; unique login email; phone; portal locale en/ar; temporary password; individual/company type; national ID; company name; emergency contact name/phone; address; notes; active/inactive/blocked status. Optional `next=lease` and a scoped `asset_id` continue directly into lease creation.

### 9.5 Leases And Contracts

**List columns:** code/status/frequency; tenant and asset; contract period/days/signing state; paid/remaining balance; next due/overdue; actions.  
**Filters:** status, frequency, start/end dates, portfolio, property, search, pagination, sort.  
**Detail tabs:** Overview, Financial, Documents, Related, History for management; tenant payload removes internal/admin-only sections.  
**Detail content:** tenant, rentable node, property, manager, dates, notice, signing, renewal chain, move-out state, rent/deposit/tax/discount/billing day, installment schedule, payment allocation, documents, move-in/move-out progress, audit timeline.  
**Actions:** contract PDF, editable DOCX, upload signed PDF, activate/terminate, renew, move-out plan/complete, statement, post payment.

**Create fields/rules:** optional portfolio and prior lease; required tenant and asset; draft/active status; monthly/quarterly/yearly frequency; required start/end with end after start; optional signed date; renewal notice 0..365; non-negative rent/deposit/tax/discount; currency; billing day 1..31; English/Arabic terms and internal notes up to 50,000 characters. Asset tenancy exclusivity and scope are checked transactionally.

**Edit delta:** status is limited by transition rules; editable signed date, renewal notice, notes, and bilingual terms. Financial schedule identity is not casually rewritten after activation.

**Signed contract:** PDF only, valid PDF signature, MIME and extension checked, max 10MB.

**Lease statuses:** draft -> active or terminated; active -> expired/terminated through lifecycle; ended leases release occupancy when no conflicting active tenancy exists. Renewal creates one linked draft replacement.

### 9.6 Installments, Rent Collection, And Follow-up

**List columns:** installment/type/sequence/status; tenant/lease; property/asset; due date/timing; due/paid/outstanding balance; latest follow-up state/assignee; actions.  
**Filters:** actionable/open/overdue/partial/paid, follow-up state, rent/deposit line type, due dates, portfolio, property, search, pagination, sort.  
**Actions:** open lease, post payment, manage follow-up.

**Follow-up fields:** contact method phone/email/WhatsApp/in person/other; outcome contacted/no answer/promise to pay/disputed/payment arranged; contact time not in future; required assignee; next follow-up today through one year; promise amount/date required for promise-to-pay; required note max 2,000.

Follow-ups are append-only. Derived states include untracked, due, promised, broken, scheduled, settled. This module records WhatsApp as a contact method; it does not send WhatsApp messages.

### 9.7 Payments

**List columns:** payment reference/type/method/status; tenant/lease/asset; received date; amount/currency; allocation and unallocated balance; actions.  
**Filters:** status, type, method, received dates, portfolio, property, search, pagination, sort.  
**Detail:** receipt priority, payment identity, tenant/lease/property, amount/method/reference/date, allocation rows, related documents, workflow, audit history.  
**Actions:** create, edit pending data, download PDF receipt for posted payment, void/reverse.

**Fields/rules:** required lease; type rent/deposit/fee; method bank transfer/cash/card; status posted/pending; optional unique reference; required received date; amount 0.01..999,999,999,999.99 with two decimals; optional notes max 5,000. Currency, portfolio, and tenant derive from the lease.

**Workflow:** pending -> posted or void; posted -> void; void is terminal. Allocation and reversal use transactions and row locks. Posted money is allocated to open installments in sequence.

### 9.8 Expenses

**List columns:** title/category/status; linked asset or maintenance ticket; vendor; incurred date; amount/currency; actions.  
**Filters:** status, category, dates, portfolio, property, search, pagination, sort.  
**Detail:** source link, expense status, amount, category, vendor, date, description, property/lease/maintenance context, workflow, history.  
**Actions:** create, edit, void/archive, open source record.

**Fields:** portfolio; optional asset; optional maintenance request; required category; required title; description; incurred date not after today; amount 0.01..999,999,999,999.99; required uppercase currency; vendor name; posted/pending status.

**Categories:** maintenance, utilities, supplies, repairs, insurance, taxes, administration, cleaning, security, management, compliance. Statuses: posted, pending, void.

### 9.9 Maintenance Requests And Tenant Sign-off

**List columns:** request ID/title/category; asset/tenant; assignee; priority; status/tenant confirmation; actions.  
**Filters:** status, pending tenant confirmation, category, priority, dates, portfolio, property, search, pagination, sort.  
**Detail:** issue and tenancy context, progress, workflow, public/internal updates, private evidence, work orders, expenses, service reports, resolution summary, tenant response, history.  
**Actions:** assign, reprioritize, comment public/internal, create work order, add expense/evidence, resolve, cancel, reopen, download PDF/DOCX service report, tenant confirm/reopen.

**Management create fields:** portfolio, required asset and tenant, optional assignee, category, priority, status, title, description, internal notes, resolution summary required when resolved, optional evidence images.  
**Tenant create fields:** own rented asset, category, priority, title, description, optional evidence; lease/tenant/portfolio derive from the signed-in user.  
**Triage fields:** assignee, priority, allowed status, internal notes, required resolution summary on resolve, comment, public-comment switch. Tenant update accepts only a comment.

**Options:** categories electricity/plumbing/AC/general; priority low/medium/high/urgent; status open/in progress/resolved/cancelled. Resolution requires tenant confirmation for closeout evidence; tenant can confirm or reopen.

**Evidence:** JPG/JPEG/PNG/WebP, max four per upload, 12 per request, 5MB each, max 8,000px, privately stored and authorization checked.

### 9.10 Maintenance Vendors And Work Orders

**Vendor list columns:** vendor/category; contact; work-order counts; status; actions.  
**Vendor filters:** status, service category, portfolio, search, pagination.  
**Vendor fields:** portfolio; required name; contact name; phone; RFC email; service category; active/inactive status; notes max 5,000.

**Work-order list columns:** order/request/status; property/tenant; vendor/internal owner; schedule/tenant access; estimated/final costs; actions.  
**Filters:** status, schedule state, tenant-access requirement, vendor, internal assignee, dates, portfolio, property, search, pagination, sort.  
**Detail:** assignment, request, scope, schedule, access requirement, estimate/final cost, completion time/notes, workflow, expense action.  
**Create fields:** vendor required, internal assignee, draft/scheduled status, schedule, estimate, required scope, tenant access.  
**Edit additions:** all statuses, final amount, completion notes.

**Transitions:** draft -> scheduled/cancelled; scheduled -> in progress/cancelled; in progress -> completed/cancelled; completed -> in progress; cancelled -> draft. Current status is always allowed for a no-op edit.

### 9.11 Documents

**List columns:** bilingual title/type/file; attached record; issue/expiry validity; internal/tenant access; actions.  
**Filters:** document type, attachment type, visibility, expiry state, issue date range, portfolio, property, search, pagination, sort.  
**Detail:** metadata, validity, attachment, access, private PDF download, history.  
**Actions:** create, edit metadata, archive, download.

**Fields/rules:** attach to asset/lease/payment and existing record ID; type; required English/Arabic title; issue date; expiry date not before issue; tenant-portal boolean; PDF file. File must have `.pdf`, PDF MIME, a valid PDF signature, and be at most 10MB.

**Types:** lease contract, signed contract, receipt, owner report, tenant statement, termination notice, move-out inspection, identity document, other. Tenant visibility is additionally restricted to safe attachment/type combinations and own tenancy; setting `is_public` alone cannot expose an arbitrary PDF.

### 9.12 Media

**List columns:** preview/title; portfolio/collection scope; filename/MIME/size; dimensions; visibility; actions.  
**Filters:** visibility, collection, portfolio for superadmin, search, pagination, sort.  
**Detail:** preview spotlight, bilingual titles/alt text, collection, scope, format, dimensions, size, uploader, history.  
**Fields:** optional portfolio; required collection; required English/Arabic title; required English/Arabic alt text; public/private visibility; image file. Allowed formats are JPEG/PNG/WebP/GIF, max 10MB and 10,000px. Global media is superadmin-only.

### 9.13 Reports And Saved Presets

**Report tabs:** Library, Overview, Collections, Costs, Operations.  
**Filters:** rolling period or custom dates, portfolio, property; module-specific reports add state/bucket/search/pagination/sort.  
**Overview cards:** currency-separated revenue, expenses, net, scheduled due/paid, collection rate, arrears, contract balance; occupancy; active leases; maintenance; period comparison.  
**Collections:** open accounts, untracked overdue accounts, follow-ups due, broken promises, monthly collections, arrears contracts, recent payments, top assets.  
**Costs:** expense categories and recent expenses.  
**Operations:** event totals, new leases, service activity, uploaded documents, operational journal, asset mix, maintenance status, backlog.  
**Library:** owner statement, property operating report, portfolio performance, rent roll, collection, arrears aging, payments, expenses, renewals, daily operations, daily archive, document expiry, move-outs, maintenance, occupancy, tenants, documents, audit, and other scope-allowed registers.

**Saved report fields:** required English/Arabic title; private/portfolio/global visibility; default flag; period, start/end, portfolio, property. Only `portfolio-report` is currently supported. Global presets cannot retain misleading property filters. Access depends on author, portfolio, visibility, and role.

### 9.14 CMS, Public Website, Navigation, And Wording

**CMS workspace:** page list, reusable section library, navigation directory, publishing/translation insights.  
**Builder:** section library, live canvas, inspector, desktop drag/drop, keyboard reorder, mobile up/down controls, optimistic persistence/rollback, visibility, reusable-content warning, bilingual editing, preview widths, publish status.  
**Page fields:** slug; bilingual title/excerpt; bilingual SEO title/description; draft/published/archived status; homepage and visible flags.  
**Section fields:** type; bilingual name; `content_en`, `content_ar`, settings arrays; active/inactive/archived status. Types: hero, role cards, workflow, dashboard preview, feature grid, operations strip, FAQ, final CTA, metrics, content.  
**Page-section controls:** attached section, order, visible, instance settings, bilingual content override.  
**Navigation fields:** parent, CMS page or validated URL, header/footer location, bilingual title, `_self`/`_blank`, order, visible.  
**Wording:** paginated module catalog, search, customized/default filters, EN/AR editor, placeholder preservation, reset, cache invalidation, and missing Arabic content queues for assets, portfolios, documents, media, CMS, navigation, and report presets.

Public publishing requires the configured bilingual content completeness; drafts may remain incomplete.

### 9.15 Opening Data

The page downloads a bilingual `.xlsx` template, accepts a maximum 10MB `.xlsx`, creates an expiring private preview, reports row-level problems, and commits only after explicit confirmation. The workbook imports assets, tenants, leases, installments, payments, allocations, and occupancy in one transaction. Duplicate codes/references, hierarchy, dates, currencies, tenancy conflicts, and payment consistency are validated before commit.

Imported tenant accounts do not receive known production passwords; portal access is handed off through a fresh 60-minute password setup link.

### 9.16 System Controls

- **Launch Readiness:** environment, HTTPS, production safety, scheduler cadence, queue health, failed jobs, storage/private files, backup age, and auditable evidence for SMTP, offsite backup, restore drill, legal approval, opening data, billing, retention, and four-role pilot. PDF/DOCX/XLSX evidence outputs exist.
- **Infrastructure Settings:** encrypted SMTP enable/host/port/scheme/username/password/from identity and scheduler PHP binary. Password is never returned to the browser or audit payload. The page is deployed and active; live SMTP values still await operator entry.
- **Email Delivery:** processing/accepted/failed logs, type/recipient/attempt/date filters, detail, XLSX export.
- **Backup Control:** queued/running/completed/failed/pruned history, one active run, private download, checksum manifest, retention/prune. Weekly schedule Sunday 02:30.
- **Data Lab:** tagged showcase dataset generation, progress, retry, and purge. Showcase accounts use `.invalid`, random passwords, inactive status, and cannot log in.
- **Notifications:** in-app operational messages, filter/search, mark one/all read. Email delivery depends on SMTP/queue activation.
- **Documentation:** role/module-filtered searchable guide index and individual bilingual guides.
- **Audit:** actor, event, subject, portfolio, date filters, scoped history, XLSX export.

## 10. Status And Workflow Reference

| Domain | Values/workflow |
|---|---|
| Portfolio | active, inactive, archived |
| User | active, inactive, suspended; tenant profile maps suspended to blocked |
| Tenant profile | active, inactive, blocked; individual/company |
| Asset | active, inactive, archived |
| Occupancy | vacant, occupied, partially occupied, reserved, maintenance |
| Lease | draft, active, expired, terminated |
| Installment | scheduled/open, partial, paid, overdue states derived from due/paid/time |
| Payment | pending, posted, void |
| Expense | pending, posted, void |
| Maintenance | open, in progress, resolved, cancelled; resolved can await tenant confirmation |
| Priority | low, medium, high, urgent |
| Work order | draft, scheduled, in progress, completed, cancelled |
| Vendor | active, inactive |
| Move-out | planned, completed, cancelled; deposit pending/full refund/partial/retained/not applicable |
| CMS page | draft, published, archived |
| CMS section | active, inactive, archived |
| Media/document visibility | public or private, with additional tenant-safe authorization |
| Backup | queued, running, completed, failed, pruned |
| Email delivery | processing, accepted, failed |
| Readiness evidence | confirmed/unconfirmed with actor, evidence, scope, and timestamp |

## 11. Reports And Export Matrix

| Output | Browser | PDF | DOCX | XLSX | Scope/filter behavior |
|---|---:|---:|---:|---:|---|
| Portfolio dashboard report | Yes | Via statement | Via statement | Yes | Period, portfolio, property |
| Owner/portfolio statement | Yes | Yes | Yes | Yes | Period and property scope |
| Property operating report | Yes | Yes | Yes | Yes | One property hierarchy and period |
| Rent roll | Yes | Yes | Yes | Yes | Search, state, portfolio, property, pagination/sort |
| Arrears aging | Yes | Yes | Yes | Yes | Search, aging bucket, portfolio, property |
| Lease renewal schedule | Yes | Yes | Yes | Yes | Queue, horizon, status, portfolio, property |
| Daily Operations Brief | Yes | Yes | Yes | Yes | Priority/type/assignee/property filters |
| Archived daily report | Yes | Yes | Yes | Yes | Immutable generated run |
| Tenant account statement | Yes | Yes | Yes | Yes | Tenant and date period |
| Lease contract | Detail | Yes authoritative | Yes working copy | No | One lease |
| Payment receipt | Detail | Yes | No | No | One posted payment |
| Maintenance service report | Detail | Yes authoritative | Yes working copy | No | One request |
| Readiness evidence | Yes | Yes | Yes | Yes | System or selected portfolio |
| Operational registers | Yes | No | No | Yes | Same list search/filter/access scope |
| Company control | Yes | No | No | Yes | Cross-portfolio source/status/attention filters |
| Audit/email delivery | Yes | No | No | Yes | Same scoped filters |

The system exports genuine OOXML `.xlsx`, not renamed CSV. There is no general CSV export in the current product. Large shared-hosting PDFs may cap visible detail at 100 rows while preserving disclosed totals; DOCX/XLSX carry the complete filtered schedule.

## 12. Notifications And Integrations

| Integration | Current state |
|---|---|
| SMTP email | Application support and settings UI exist; production SMTP is not yet proven. Password reset and readiness test email are logged/tracked. |
| In-app notifications | Implemented for operational events with read/unread state. |
| WhatsApp | No API. It is only a collection follow-up contact-method value. |
| Payment gateway | None. Payments are manually recorded and allocated. |
| Bank reconciliation | None. Reference and method are stored, but no statement feed/import matching exists. |
| E-signature | None. The system generates contracts and accepts a signed PDF upload; no cryptographic e-sign or identity ceremony. |
| Maps | Leaflet + OpenStreetMap tiles, clustered markers, configurable tile URL, localized list fallback. No geocoding or route planning. |
| Storage | Laravel local/public disks; private documents and backups; optional Hostinger public-storage mirror. S3 is framework-configurable but not product-integrated. |
| Queue | Database queue, drained each minute by scheduled `queue:work --stop-when-empty`. |
| Scheduler | Heartbeat every minute; queue every minute; lease/installment status daily 00:05; daily report archive 06:00 default; weekly backup Sunday 02:30. Production cron is not yet evidenced. |
| External APIs | No business-critical external API besides map tile loading and SMTP. |

## 13. Database Model And Relationship Map

### Core chain

```mermaid
erDiagram
    PORTFOLIO ||--o{ USER : contains
    PORTFOLIO ||--o{ ASSET : contains
    ASSET ||--o{ ASSET : parent_of
    ASSET ||--o{ ASSET_STAKEHOLDER : assigned
    USER ||--o{ ASSET_STAKEHOLDER : owner_or_manager
    PORTFOLIO ||--o{ TENANT_PROFILE : contains
    USER ||--o| TENANT_PROFILE : portal_account
    TENANT_PROFILE ||--o{ LEASE : signs
    ASSET ||--o{ LEASE : leaseable
    LEASE ||--o{ LEASE_INSTALLMENT : schedules
    LEASE ||--o{ PAYMENT : receives
    PAYMENT ||--o{ PAYMENT_ALLOCATION : allocates
    LEASE_INSTALLMENT ||--o{ PAYMENT_ALLOCATION : settles
    LEASE ||--o| LEASE_MOVE_OUT : closes_with
    ASSET ||--o{ MAINTENANCE_REQUEST : receives
    TENANT_PROFILE ||--o{ MAINTENANCE_REQUEST : submits
    MAINTENANCE_REQUEST ||--o{ MAINTENANCE_UPDATE : records
    MAINTENANCE_REQUEST ||--o{ MAINTENANCE_ATTACHMENT : evidences
    MAINTENANCE_REQUEST ||--o{ MAINTENANCE_WORK_ORDER : dispatches
    MAINTENANCE_VENDOR ||--o{ MAINTENANCE_WORK_ORDER : performs
    ASSET ||--o{ EXPENSE_ENTRY : incurs
    MAINTENANCE_REQUEST ||--o{ EXPENSE_ENTRY : incurs
    ASSET ||--o{ DOCUMENT : owns
    LEASE ||--o{ DOCUMENT : owns
    PAYMENT ||--o{ DOCUMENT : owns
```

### Model responsibilities

| Model | Important relationships/purpose |
|---|---|
| `Portfolio` | Owner user; users, assets, tenants, leases, payments, maintenance, vendors, work orders, expenses, documents; module settings and showcase tag. |
| `User` | Portfolio and roles; owned portfolios; tenant profile; payment recorder; assignments; submitted/assigned maintenance; uploaded docs/media. |
| `Asset` | Portfolio; self parent/children; stakeholders; polymorphic leases/documents; maintenance and expenses. |
| `AssetStakeholder` | Asset, portfolio, user, relationship type, primary flag; represents ownership and management. |
| `TenantProfile` | Portfolio and portal user; onboarder; leases, payments, maintenance. |
| `Lease` | Portfolio, tenant, manager, previous/renewal lease, move-out, polymorphic rentable asset, installments, payments, follow-ups, documents. |
| `LeaseInstallment` | Lease; payment allocations; collection follow-ups/latest follow-up. |
| `Payment` | Portfolio, lease, tenant, recorder, allocations, documents. |
| `PaymentAllocation` | Connects payment money to one installment. |
| `CollectionFollowUp` | Portfolio, lease, installment, recorder, assignee; immutable collection contact history. |
| `LeaseMoveOut` | Portfolio, lease, initiator/completer, handover and deposit decisions. |
| `MaintenanceRequest` | Portfolio, asset, lease, tenant, submitter, assignee, resolver; updates, evidence, work orders, expenses. |
| `MaintenanceUpdate` | Maintenance request, author, audience/visibility, status transition, and append-only progress/comment history. |
| `MaintenanceAttachment` | Maintenance request, uploader, private image path, MIME/size/dimensions, and evidence metadata. |
| `MaintenanceWorkOrder` | Portfolio, request, vendor, creator, assignee, schedule/cost/completion. |
| `MaintenanceVendor` | Portfolio contractor and work orders. |
| `ExpenseEntry` | Portfolio; optional asset, lease, maintenance request; creator. |
| `Document` | Portfolio/uploader plus polymorphic asset, lease, or payment attachment; always a private-controlled PDF record. |
| `MediaFile` | Portfolio/uploader; CMS and portfolio image metadata. |
| `CmsPage` | Ordered page sections and navigation items. |
| `CmsSection` | Reusable bilingual section content and page attachments. |
| `CmsPageSection` | Page/section pivot with order, visibility, and instance settings. |
| `NavigationItem` | CMS page, parent/children, header/footer order and visibility. |
| `LabelOverride` | Optional portfolio/global English/Arabic wording override. |
| `ReportPreset` | Portfolio/user ownership, filters, visibility, default state. |
| `OperationalReadinessCheck` | System/portfolio evidence, confirmer, status. |
| `EmailDeliveryLog` | Recipient, type, status, attempts, failure details, optional user/portfolio. |
| `SystemBackupRun` | Initiator, source/status, archive/checksum/manifest/failure. |
| `DailyOperationsReportRun` | Portfolio/initiator, immutable generated files and state. |
| `ShowcaseDataset` | Tagged generation run owning showcase portfolios/users. |
| `InfrastructureSetting` | Encrypted SMTP settings, scheduler binary, updater. |

## 14. Frontend Components And Design System

Key components that define the current UI:

- `AdminLayout`, `AdminSidebar`, `AdminTopbar`, `AccountMenu`, `GlobalSearch`, `PropertyContextSwitcher`.
- `DataTable`, `DesktopRecordTable`, `MobileRecordList`, `TableToolbar`, `TablePagination`.
- `WorkspaceHeader`, `WorkspacePanel`, `MetricGrid`, `StatusBadge`, `RecordActions`.
- `ResourceFormShell`, `ResourceDetailShell`, `ResourceHeader`, `ResourceDetailTabs`.
- `DecisionCardGrid`, `WorkflowActionPanel`, `ResourceProgressPanel`, `DocumentStrip`, `HistoryTimeline`, `RelatedRecordsTable`.
- Dashboard: `OperationsDashboard`, `TenantDashboard`, `OperationsMetrics`, `PropertyPerformanceGrid`, `ActionQueue`, `PlatformCompositionPanel`, `LaunchReadinessPanel`.
- Map: `GeographicMap`, `MapFilters`, `MapWorkspace`, `PropertyMapDirectory`, `PropertyMapDetail`.
- CMS: builder canvas, section selection/library, inspector/editor, media picker, preview, navigation forms, and optimistic reorder hooks.
- Reports: `ReportLibrary`, `CurrencyPositionGrid`, `ReportComparison`, `ReportPulse`, `BreakdownCards`, `ReportJournal`, and report-specific record sections.

The production CSS is route-split. The main built CSS is 311.43KB on disk. The visual stack is white/gray with green primary actions, restrained status colors, responsive cards, Manrope for English, and IBM Plex Sans Arabic.

## 15. Detected Problems And Unfinished Work

### Critical launch blockers

1. **SMTP is not proven.** Password reset, readiness email, Arabic rendering, queued delivery, and failure reporting need receipt evidence through a real mailbox.
2. **The scheduler is not proven.** Readiness requires three distinct one-minute heartbeats spanning at least 90 seconds, a draining queue, and zero failed jobs.
3. **No real portfolio is reconciled.** Showcase records are useful for scale testing but cannot prove real opening balances, occupancy, contracts, deposits, or payment totals.
4. **No four-role pilot is complete.** Real superadmin, owner, manager, and tenant workflows have not been run for 30 days.
5. **Legal/business rules are unapproved.** Bilingual lease terms, billing rules, retention, opening balances, and local legal obligations require owner/legal approval.

### Product limitations that must stay explicit

- Manual payments only; no gateway, bank feed, refunds integration, or automated reconciliation.
- Operational finance only; no chart of accounts, journal entries, VAT filing, or double-entry accounting.
- Signed PDF upload only; no cryptographic e-signature.
- No WhatsApp/SMS API; contact method is tracked manually.
- OpenStreetMap public tiles have no service-level agreement.
- Shared-hosting queue architecture is appropriate for bounded jobs, not heavy real-time workloads.

### Design and engineering debt

1. **Authorization has multiple sources of truth.** Spatie permissions, access classes, portfolio module toggles, portfolio scope, and manager assignments are all valid but difficult to reason about. The access classes are authoritative; this should be stated in developer documentation and enforced through architecture tests.
2. **Route policy is not obvious from `route:list`.** Most routes show only authentication/module middleware while role enforcement happens inside requests/controllers/access classes. A generated access catalog would reduce audit effort.
3. **Property terminology is mixed.** Database and older UI concepts say “asset”; business navigation says “property.” The model can stay `Asset`, but user-facing naming should consistently use Property, Building, Floor, Unit, and Space.
4. **CSS remains large.** It is modular, not one giant file anymore, but 20,923 source lines and a 316KB main bundle still require a style-budget gate and dead-style review.
5. **Generated route/action TypeScript dominates file-size reports.** These files are machine-generated and not a maintainability problem, but they obscure human-module size metrics unless excluded from architecture reports.
6. **Generic shared resource labels still use source-text translation in a few primitives.** Examples include “Overview,” “Financial,” and form framing. They resolve through the wording layer, but typed translation keys would be clearer and safer.
7. **No bulk workflow exists.** This is correct for money and contracts, but a future real pilot may justify narrow bulk actions such as assign manager, export selected, or maintenance reassignment. Do not add generic bulk delete.
8. **Production status evidence is split between repository docs and live readiness.** `/system/readiness` should remain the source of truth, and release documentation should be generated from it after every deployment.

### UI risks to watch in the real pilot

- Very long detail histories and related-record collections depend on bounded presenter payloads; monitor response size as real years of history accumulate.
- The mobile-card design intentionally shows only three important values; confirm users can identify the right lease/payment/request without opening too many records.
- Report and map performance targets were tested with showcase data, but a real portfolio may have less-clean titles, unusually deep hierarchies, and document-heavy records.
- CMS is powerful enough to confuse an occasional editor. The visual builder needs pilot observation, not more controls.

## 16. MVP Goal And Next Operational Sequence

The goal should be **a trustworthy 30-day pilot for one reconciled real portfolio**, not another redesign.

1. Configure SMTP in `/system/settings`, create the Hostinger mailbox, clear caches, and prove password-reset and Arabic readiness-email receipt.
2. Register the one-minute Hostinger cron and obtain three heartbeat samples, a drained queue, and zero failed jobs.
3. Download the fresh deployment backup, verify its checksums, and retain it off-server.
4. Import one approved non-showcase portfolio through `/opening-data`.
5. Reconcile properties, rentable units, occupancy, owners, managers, tenants, leases, deposits, installments, payments, currencies, and PDFs against source records.
6. Assign one real participant per role and run login/recovery, contract, payment/receipt, tenant view, maintenance, work order, resolution, tenant sign-off, and service report in English/Arabic on desktop and 390px mobile.
7. Run the pilot for 30 days. Fix only reproduced defects and add a regression test for each one.

### Definition of fully operational MVP

- GitHub revision and Hostinger deployment are identical.
- `/up` is 200 and warm primary pages remain under three seconds.
- SMTP and scheduler evidence are green; queue and failed jobs remain clean.
- A fresh recoverable backup exists off-server.
- One real portfolio is financially reconciled.
- No critical/high defects or authorization leaks remain.
- Tenant PDFs and report XLSX signatures are valid.
- English, Arabic, RTL, desktop, and mobile complete the same critical workflow.
- Legal, billing, and retention rules are approved and recorded.
- Launch Readiness reaches 12/12 after the pilot, not before it.

## 17. Verification Evidence

| Check | Result |
|---|---|
| Fresh PHPUnit run | 653 passed, 37,915 assertions |
| Route inventory | 237 application endpoints |
| Application models | 31 |
| Recorded Playwright/axe baseline | 70 scenarios |
| Recorded responsive widths | 390, 768, 1024, 1440 |
| Recorded PHPStan state | Zero current errors with 13 accepted legacy baseline entries |
| Production health on report date | `/up` HTTP 200 |
| Production latest-settings route | Authenticated page active; unauthenticated request correctly redirects to login |
| Production release | Revision and Vite manifest match GitHub/local build |
| Production documents | Maintenance PDF `%PDF-` and report XLSX `PK` signatures verified |

## 18. Report Limitations

- The report does not include tenant PII, production credentials, SMTP secrets, or private file contents.
- The current source was fully inspected and PHP-tested; the complete 70-scenario browser and accessibility suite was rerun for this UI release.
- Production authenticated internals were smoke-tested after deployment without modifying operational records.
- Legal compliance is a business/legal approval, not something source inspection can certify.

## 19. Page-By-Page Functional Specification

This section is the operator-facing page catalog. Field-level form rules remain in Section 9 and the additional control forms are in Section 20. Access always includes the account gate (`auth`, active account, permanent password) unless the page is marked Public or Guest.

### 19.1 Public, authentication, and account pages

| Route / route name | Page and purpose | Visible areas and actions | Access | React entry or feature component |
|---|---|---|---|---|
| `/` · `home` | Public homepage. Presents the CMS-configured company proposition and portal entry. | Header/footer navigation, CMS sections, role/workflow content, login CTA, localized page metadata. | Public | `pages/public/home.tsx` -> `HomePage` |
| `/pages/{slug}` · `pages.show` | Public content page. Renders one published CMS page. | Bilingual title, excerpt, ordered visible sections, public navigation. | Public; published and visible records only | `pages/public/page.tsx` -> `ContentPage` |
| `/login` · `login` | Sign in. Starts a role-aware session. | Email, password, remember-me, recovery link, language switch. | Guest | `pages/auth/login.tsx` -> `LoginPage` |
| `/account-recovery` · `password.request` | Password recovery request. | Email field and non-enumerating completion feedback. | Guest | `pages/auth/forgot-password.tsx` -> `ForgotPasswordPage` |
| `/reset-password/{token}` · `password.reset` | Password setup/reset. Used for recovery and portal handoff. | Email, token, new password, confirmation. | Guest with valid token/email | `pages/auth/reset-password.tsx` -> `ResetPasswordPage` |
| `/profile` · `profile.index` | Personal account settings. | Identity summary, name, phone, language, password-change form, role/portfolio context. | SA/O/M/T; own account only | `pages/admin/profile/index.tsx` -> `ProfilePage` |
| `/notifications` · `notifications.index` | Notification inbox. | Search, read state, event type, notification list, target link, mark one/all read. | SA/O/M/T; own notifications only | `pages/admin/notifications/index.tsx` -> notifications `IndexPage` |
| `/documentation` · `documentation.index` | Role-aware operating manual. | Searchable guide directory, module groups, progress/navigation cues. | SA/O/M/T; role and enabled-module filtered | `pages/admin/documentation/index.tsx` |
| `/documentation/{guide}` · `documentation.show` | One operating guide. | Bilingual sections, workflow steps, route links, previous/next guide navigation. | SA/O/M/T; guide policy filtered | `pages/admin/documentation/show.tsx` |

### 19.2 Command, search, and operating-control pages

| Route / route name | Page and purpose | Visible areas, filters, and actions | Access | Component |
|---|---|---|---|---|
| `/dashboard` · `dashboard` | Role-specific command center. | Period/property context, KPI grid, operations panels, queues, setup checklist, next actions; tenant receives only own lease/payment/service data. | SA/O/M/T with role-specific payload | `DashboardPage`, `OperationsDashboard`, `TenantDashboard` |
| `/my-lease` · `tenant-portal.lease` | Tenant contract workspace. | Own concurrent/historical lease selector, rental identity, dates, rent/deposit, balance/days remaining, installment schedule, safe documents, contract PDF/DOCX and statement PDF. | T; tenant profile and lease ownership enforced server-side | tenant-portal `LeasePage` |
| `/my-payments` · `tenant-portal.payments` | Tenant payment workspace. | Own posted/pending/void payments, currency-separated balances, status/date/lease/search filters, pagination, posted receipts, manual-payment guidance. | T; tenant profile ownership enforced server-side | tenant-portal `PaymentsPage` |
| `/my-documents` · `tenant-portal.documents` | Tenant document workspace. | Safe own lease/payment documents only, type/date/lease/search filters, private authorized downloads, pagination. No tenant-wide upload. | T; attachment type, portal visibility, portfolio, and tenancy enforced server-side | tenant-portal `DocumentsPage` |
| `/company-control` · `company-control.index` | Cross-portfolio platform control. | Source/status/attention filters, platform metrics, portfolio composition, operational cards, XLSX export. | SA only | company-control `IndexPage` |
| `/portfolio-control` · `portfolio-control.index` | Compare accessible properties. | Search/status/risk sorting, occupancy, collection, arrears, cash flow, maintenance pressure, property links. | SA/O/M scoped | portfolio-control `IndexPage` |
| `/action-center` · `action-center.index` | Prioritized daily work queue. | Type, priority, assignee, portfolio/property filters; collection, service, renewal, move-out, and expiring-document items; PDF/DOCX/XLSX. | SA/O/M scoped | action-center `IndexPage` |
| `/property-map` · `property-map.index` | Geographic operating view. | Portfolio/property filters, map/list mode, clusters, location completeness metrics, selected-property detail. | SA/O/M; assets module and assignment scope | property-map `IndexPage`, `GeographicMap` |
| `/property-explorer` · `property-explorer.index` | Hierarchy browser for one property tree. | Property selector, hierarchy/breadcrumb, node metrics, child records, operating links. | SA/O/M; assets module and assignment scope | `pages/admin/assets/explorer.tsx` |
| `/global-search` · `global-search` | Global operational search endpoint used by topbar/mobile search. | Grouped results for accessible properties, people, leases, payments, maintenance, and documents. | SA/O/M/T; tenant receives own-safe results | `GlobalSearch`, `MobileSearchSheet` |
| `/search` · `search.index` | Full global-search results workspace. | Search field, result count, grouped accessible results, exact-code direct target, role-scoped empty/minimum-query states. | SA/O/M/T; identical scope to `/global-search` | search `ResultsPage` |

### 19.3 Portfolio, property, building, unit, and space pages

| Route / route name | Page and purpose | Visible areas / tabs | Actions | Access |
|---|---|---|---|---|
| `/portfolios` · `portfolios.index` | Portfolio directory. | Table columns and filters in 9.1, metrics, module state, financial position. | Open, create where allowed, export, filter/sort/page. | SA all; O/M own portfolio. |
| `/portfolios/create` · `portfolios.create` | Create client portfolio. | Identity, contact/location, default currency, initial status, ten module switches. | Save/cancel. | SA only. |
| `/portfolios/{portfolio}` · `portfolios.show` | Portfolio source of truth. | Overview, setup progress, properties/users/tenants/contracts/payments/service, documents, history. | Edit, continue setup, open registers/reports, archive where allowed. | SA any; O/M own portfolio; M read-only portfolio metadata. |
| `/portfolios/{portfolio}/edit` · `portfolios.edit` | Edit portfolio and module availability. | Same controlled fields as create; archive is not a form value. | Update/cancel. | SA any; O own portfolio. |
| `/assets` · `assets.index` | Property hierarchy directory. | Property/building/floor/unit/space table, filters in 9.2, occupancy and valuation metrics. | Open, add record, building setup, export. | SA/O/M; module and assignment scoped. |
| `/assets/create` · `assets.create` | Create one property-tree record. | Portfolio, parent, type, usage, bilingual identity, occupancy, value, area, address/map, owner/manager. | Save/cancel. | SA/O/M; manager must create below an assigned root. |
| `/assets/building-setup` · `assets.structure.create` | Atomic building/floor/unit generator. | Building identity, floor/unit counts, usage/type, owner/manager, valuation, dimensions, location. | Preview summary, create hierarchy. | SA/O. |
| `/assets/{asset}` · `assets.show` | Full management of one property node. | **Overview:** identity, hierarchy, occupancy, ownership, location. **Financial:** valuation, collection, arrears, revenue/expenses. **Documents:** attached PDFs. **Related:** children, leases, maintenance, expenses. **History:** audit timeline. | Edit, create child/tenant/lease/payment/expense/request/document, open map/explorer/report, archive. | SA/O/M scoped to portfolio and assigned hierarchy. |
| `/assets/{asset}/edit` · `assets.edit` | Edit one property node. | Same identity, operational, financial, location, and assignment fields described in 9.2. | Update/cancel. | SA/O/M scoped. |

### 19.4 Users, managers, tenant profiles, and portal access

| Route / route name | Page and purpose | Visible areas / tabs | Actions | Access |
|---|---|---|---|---|
| `/users` · `users.index` | Access-account directory. | User/contact, roles, portfolio, portal/account state, workload, filters, pagination, XLSX. | Open, create, edit, portal handoff, manager assignments. | SA all; O own managers/tenants; M self plus assigned tenants. |
| `/users/create` · `users.create` | Create a login account. | Portfolio, identity, locale, status, role, temporary password. | Save/cancel. | SA any role; O manager/tenant; M assigned tenant only. |
| `/users/{user}` · `users.show` | User account source of truth. | Identity, status, role/portfolio, assigned properties, stakeholder roles, service workload, documents, history. | Edit, manage assignments, generate portal link, deactivate where allowed. | Management role with target-specific `UserAccess`; never self-role management. |
| `/users/{user}/edit` · `users.edit` | Edit a manageable account. | Identity/contact/locale/status/role/portfolio constrained by actor. | Update/cancel. | Same target rules as user detail. |
| `/users/{user}/property-assignments` · `users.property-assignments.edit` | Define manager operating scope. | Searchable top-level roots, selected assignments, descendant-scope explanation. | Save assignment set. | SA or portfolio owner; target must be manager. |
| `/portal-accounts/{user}/access` · `users.portal-access.show` | Safe account handoff. | Account state, setup-link status/expiry, delivery steps. | Generate a fresh 60-minute setup link. | SA/O/M when target is manageable. |
| `/tenants` · `tenants.index` | Tenant-profile directory. | Identity/contact, profile type, current rental, profile and portal state, filters, XLSX. | Open, create, edit, statement, portal handoff. | SA/O/M scoped. |
| `/tenants/create` · `tenants.create` | Create tenant account and operating profile. | Account, locale/password, individual/company identity, emergency contact, address, notes, status. | Save; optional continue directly to a prefilled lease. | SA/O/M within assignment scope. |
| `/tenants/{tenant}` · `tenants.show` | Tenant source of truth. | **Overview:** identity/contact/emergency/account. **Financial:** paid/balance/arrears. **Related:** leases, payments, service. **Documents. History.** | Edit, statement, portal link, create lease/payment/request/document where permitted. | SA/O/M scoped. |
| `/tenants/{tenant}/edit` · `tenants.edit` | Edit tenant/account state. | Same fields as create except protected relationship identity. | Update/cancel. | SA/O/M scoped. |
| `/tenants/{tenant}/account-statement` · `tenants.statement.show` | Full tenant ledger. | Summary, contracts, installments, payments, documents, maintenance; date filters and currency-separated totals. | PDF, DOCX, XLSX. | SA/O/M scoped. |

### 19.5 Contracts, collection, and payments

| Route / route name | Page and purpose | Visible areas / tabs | Actions | Access |
|---|---|---|---|---|
| `/leases` · `leases.index` | Contract directory. | Code/status/frequency, tenant/property, dates, signing, paid/balance/next due/arrears, filters, XLSX. | Open, create, edit, contract, renewal/move-out. | SA/O/M scoped. |
| `/leases/create` · `leases.create` | Create lease and schedule. | Tenant/asset, status/frequency/dates, rent/deposit/tax/discount/currency/billing day, terms and notes. | Save/cancel; schedule created transactionally. | SA/O/M scoped. |
| `/leases/{lease}` · `leases.show` | Contract source of truth. | **Overview:** parties, property, dates, signing/renewal/move-out. **Financial:** schedule, allocations, balances. **Documents. Related. History.** | Edit, contract PDF/DOCX, signed PDF upload, renew, move-out, statement, post payment, terminate where valid. | SA/O/M scoped; T own contract with internal data removed. |
| `/leases/{lease}/edit` · `leases.edit` | Controlled contract update. | Allowed status transition, signed date, notice, notes, bilingual terms. | Update/cancel. | SA/O/M scoped. |
| `/leases/{lease}/renew` · `leases.renew` | Create linked renewal draft. | Prefilled source lease, next term dates and financial contract fields. | Save replacement draft. | SA/O/M scoped; one replacement per source. |
| `/lease-renewals` · `lease-renewals.index` | Expiry and renewal queue. | Horizon/status/property filters, contact dates, days remaining, outstanding balance, replacement link. | Open/edit/renew, PDF/DOCX/XLSX. | SA/O/M scoped. |
| `/leases/{lease}/move-out` · `leases.move-out.edit` | Plan and complete handover. | Move-out date, notice, key return, deposit decision/amount, notes, termination/inspection documents. | Save plan, complete when guarded requirements pass, cancel plan. | SA/O/M scoped. |
| `/lease-move-outs` · `lease-move-outs.index` | Move-out operating queue. | Planned/completed/cancelled state, dates, property/tenant, deposit and evidence state. | Open lease/plan, filter/export. | SA/O/M scoped. |
| `/rent-collection` · `rent-collection.index` | Installment collection register. | Installment state, tenant/lease/property, due/paid/outstanding, timing, latest follow-up and assignee. | Open lease, post payment, manage follow-up, XLSX. | SA/O/M scoped. |
| `/rent-collection/{installment}/follow-up` · `rent-collection.follow-up` | Append-only collection case. | Balance snapshot, contact timeline, promise state, assignee and next action. | Record contact/outcome/promise/next follow-up. | SA/O/M scoped. |
| `/payments` · `payments.index` | Payment register. | Reference/type/method/status, tenant/lease/property, received date, amount, allocation, filters, XLSX. | Open, create, edit pending data, receipt, void. | SA/O/M scoped. |
| `/payments/create` · `payments.create` | Post or stage manual payment. | Lease, type, method, status, reference, date, amount, notes; identity/currency derive from lease. | Save/cancel. | SA/O/M scoped. |
| `/payments/{payment}` · `payments.show` | Payment source of truth. | Receipt priority, identity, parties/property, amount/method/date, allocations, documents, workflow, history. | Receipt PDF for posted payment, edit, void/reverse. | SA/O/M scoped; T own posted payment. |
| `/payments/{payment}/edit` · `payments.edit` | Edit mutable payment data. | Transition-safe status and permitted metadata. | Update/cancel. | SA/O/M scoped. |

### 19.6 Expenses, maintenance, work orders, contractors, documents, and media

| Route / route name | Page and purpose | Visible areas / tabs | Actions | Access |
|---|---|---|---|---|
| `/expenses` · `expenses.index` | Expense register. | Title/category/status, source record, vendor/date/amount/currency, filters, XLSX. | Open, create, edit, void/archive. | SA/O/M scoped. |
| `/expenses/create` · `expenses.create` | Record operating cost. | Portfolio, property/request source, category, title/description, date, amount/currency, vendor, status. | Save/cancel. | SA/O/M scoped. |
| `/expenses/{expense}` · `expenses.show` | Expense source of truth. | Cost, source relationships, workflow, property/lease/request context, history. | Edit, open source, void. | SA/O/M scoped. |
| `/maintenance-requests` · `maintenance-requests.index` | Service request queue. | ID/title/category, property/tenant/assignee, priority/status/tenant confirmation, filters, XLSX. | Open, create; management filters/actions differ from tenant. | SA/O/M scoped; T own requests only. |
| `/maintenance-requests/create` · `maintenance-requests.create` | Open service request. | Management sees portfolio/property/tenant/assignee/internal fields; tenant sees own rented property and public issue fields; evidence upload. | Save/cancel. | SA/O/M scoped or T own tenancy. |
| `/maintenance-requests/{request}` · `maintenance-requests.show` | Service case source of truth. | **Overview:** issue, tenancy, progress. **Related:** updates, evidence, work orders, expenses. **Documents/service reports. History.** | Assign, triage, comment, attach evidence, create work order/expense, resolve/cancel/reopen, PDF/DOCX, tenant confirm/reopen. | SA/O/M scoped; T own request with private fields removed. |
| `/maintenance-requests/{request}/attachments/create` | Add private evidence. | Image picker, evidence limits, request context. | Upload/cancel. | SA/O/M scoped or owning T. |
| `/maintenance-requests/{request}/resolution-response` | Tenant closeout decision. | Resolution summary, completed work, response choice, tenant comment. | Confirm resolution or reopen. | Owning T only. |
| `/maintenance-work-orders` · `maintenance-work-orders.index` | Contractor work register. | Order/request/status, property/tenant, vendor/owner, schedule/access, estimate/final amount. | Open/edit, filter/export. | SA/O/M scoped. |
| `/maintenance-requests/{request}/work-orders/create` | Create one work order. | Vendor, internal owner, status, schedule, access, scope, estimate. | Save/cancel. | SA/O/M scoped; request must be open and have no active order. |
| `/maintenance-work-orders/{order}` · `maintenance-work-orders.show` | Work-order source of truth. | Assignment, request, scope, schedule/access, costs, completion, workflow/history. | Edit, open request/vendor, create linked expense after work. | SA/O/M scoped. |
| `/maintenance-vendors` · `maintenance-vendors.index` | Contractor directory. | Vendor/category/contact/status, work-order counts, filters. | Open, create, edit, deactivate. | SA/O/M scoped. |
| `/maintenance-vendors/{vendor}` · `maintenance-vendors.show` | Contractor source of truth. | Contact/category/status/notes, related work and cost history. | Edit, open work orders, deactivate. | SA/O/M scoped. |
| `/documents` · `documents.index` | Private document register. | Title/type/file, attached record, validity, tenant access, filters, XLSX. | Open, upload, edit metadata, download, archive. | SA/O/M scoped. |
| `/documents/{document}` · `documents.show` | Document source of truth. | Metadata, validity, attachment, access decision, private download, history. | Download, edit, archive. | SA/O/M scoped; tenant file access only through safe own-record links. |
| `/media-files` · `media-files.index` | Image library. | Preview/title, portfolio/collection, format/size/dimensions/visibility, filters, XLSX. | Open, upload, edit, archive/file view. | SA/O/M scoped; global media SA only. |
| `/media-files/{media}` · `media-files.show` | Media source of truth. | Image preview, bilingual metadata/alt text, collection, scope, format, dimensions, uploader, history. | View file, edit, archive. | SA/O/M scoped. |

### 19.7 Reports, CMS, audit, and system pages

| Route / route name | Page and purpose | Visible areas and actions | Access |
|---|---|---|---|
| `/reports` · `reports.index` | Reporting command center. | Library/Overview/Collections/Costs/Operations tabs; period/portfolio/property filters; metrics and source links; XLSX. | SA/O/M scoped. |
| `/reports/statement` · `reports.statement` | Owner/portfolio statement. | Currency-separated position, collection/payment/expense records, period/property filters, PDF/DOCX/XLSX. | SA/O/M scoped. |
| `/reports/properties/{asset}` · `reports.properties.show` | One-property operating report. | Overview/collections/costs/service/activity tabs over the authorized hierarchy; PDF/DOCX/XLSX. | SA/O/M scoped. |
| `/reports/rent-roll` · `reports.rent-roll.index` | Rentable-unit and lease register. | Search, state, portfolio/property, pagination/sort, financial totals, PDF/DOCX/XLSX. | SA/O/M scoped. |
| `/reports/arrears-aging` · `reports.arrears-aging.index` | Aging analysis. | Search, aging bucket, portfolio/property, pagination/sort, currency totals, PDF/DOCX/XLSX. | SA/O/M scoped; reports and payments modules. |
| `/reports/saved` · `reports.saved.index` | Saved report scopes. | Author/visibility/default state, scope summary, card directory. | Create, open, duplicate, edit/delete where owned. SA/O/M scoped. |
| `/reports/saved/{preset}` · `reports.saved.show` | Saved report detail. | Identity, filters, visibility, generated operating report view and output links. | Author, portfolio-visible user, or global visibility under `ReportAccess`. |
| `/reports/daily-operations` · `reports.daily-operations.index` | Immutable daily report archive. | Date/status/portfolio history, run metrics. | Generate, open, download, delete failed/allowed runs. SA/O. |
| `/audit-logs` · `audit-logs.index` | Activity register. | Actor/event/subject/portfolio/date filters, scoped table, XLSX. | SA global; O/M scoped. |
| `/cms` · `cms.index` | Website control workspace. | Pages, reusable sections, navigation, publishing/translation metrics. | Create/edit/archive records, open visual builder. SA only. |
| `/cms/pages/{page}` · `cms.pages.show` | Visual page builder. | Section library, canvas, inspector, device preview, visibility/order/content overrides. | Attach/detach/reorder/edit/publish. SA only. |
| `/wording` · `wording.index` | Interface wording control. | Search, module/group, default/customized state, EN/AR editor, missing-content queues. | Save/reset override. SA only. |
| `/opening-data` · `opening-data.index` | Controlled opening-data import. | Steps, template, upload, private preview, row errors, commit summary. | Download template, preview, discard, atomic import. SA/O. |
| `/system/readiness` · `system-readiness.index` | Launch gate. | Automatic infrastructure checks, evidence checks, portfolio readiness, blockers, reports. | Confirm/revoke evidence, test email, PDF/DOCX/XLSX. SA only. |
| `/system/settings` · `infrastructure-settings.index` | Infrastructure configuration. | SMTP enabled/state, non-secret fields, password-configured indicator, scheduler binary/command, mail/queue/scheduler checks. | Update settings. SA only. |
| `/system/email-delivery` · `email-delivery.index` | Email delivery register. | Status/type/recipient/attempt/date filters, attempts metrics, XLSX. | Open attempt detail/export. SA only. |
| `/system/backups` · `system-backups.index` | Recovery package control. | Status/source/date history, metrics, manifest/checksum/failure details. | Queue backup, download completed package, prune controlled record. SA only. |
| `/system/showcase-data` · `showcase-data.index` | Tagged stress-data lab. | Dataset status/progress/targets/history. | Generate, retry failed, typed-confirmation purge. SA only. |

## 20. Form, Permission, And Scope Contracts

### 20.1 Account and infrastructure forms

| Form | Inputs and validation | Relationship/effect | Access |
|---|---|---|---|
| Login | `email` required email and normalized lowercase; `password` required; `remember` boolean. Login is throttled and rejects inactive/suspended accounts. | Creates Laravel web session; updates last login. | Guest. |
| Recovery request | `email` required email, normalized lowercase. | Creates password-reset token and tracked delivery attempt without revealing account existence. | Guest. |
| Password setup/reset | token and email required; password required, confirmed, Laravel defaults, minimum 8. | Replaces password, clears forced-reset state, invalidates token. | Valid token holder. |
| Profile details | name required max 255; phone optional max 30; locale `en` or `ar`. | Updates own `users` record only. Email/role/portfolio are intentionally not self-editable. | Any signed-in user. |
| Profile password | Current password required unless account is in forced-reset state; new password required and confirmed using Laravel defaults. | Replaces own password and clears forced-reset state. | Any signed-in user. |
| Infrastructure settings | Mail enabled; host without whitespace/path/port; port 1..65535; scheme smtp/smtps; username/password/from identity; clear-password flag; absolute safe scheduler PHP path max 500. All SMTP fields and a password are required when mail is enabled. | Upserts one encrypted `InfrastructureSetting`; password is write-only; exposes generated cron command, never the secret. | SA only. |
| Readiness evidence | Catalog key, confirmed boolean, evidence 3..1000 when confirmed, portfolio required for portfolio-scoped keys. | Upserts auditable system/portfolio evidence with actor and time. | SA only. |
| Notification filters | status all/unread/read; type all/maintenance/payment/lease/document; search max 120. | Filters the current user's notification collection. | Own inbox. |
| Wording override | group max 100, key max 500, English and Arabic required max 2,000. | Creates/updates a wording override and invalidates translation cache; reset deletes override. | SA only. |

### 20.2 Relationship and upload rules

- A property parent must belong to the selected portfolio. Managers may select only assigned roots/descendants.
- A rentable lease asset, tenant, manager, currency, and portfolio are cross-checked; conflicting active tenancy is rejected inside a transaction.
- Payments do not accept arbitrary tenant, portfolio, or currency. Those values derive from the selected lease.
- Payment allocation connects `payments` to `lease_installments`; posted payment creation and void reversal use transactions and row locks.
- Expenses may link to a property, lease, or maintenance request only inside the authorized portfolio/property scope.
- Maintenance requests derive tenant/lease context for tenant submissions and prevent a tenant from selecting another tenant's property.
- A work order belongs to one maintenance request and one same-portfolio vendor. Only one active order is allowed per request in the current workflow.
- Documents attach polymorphically to an asset, lease, or payment. Uploads are private, PDF-only, signature/MIME/extension checked, maximum 10MB.
- Signed contracts, termination notices, and move-out inspections are PDF-only. Maintenance evidence is image-only with count, dimension, type, and size limits described in 9.9.
- Media files are image-only and can be global only when uploaded by a superadmin.

### 20.3 Exact role and data-scope rules

| Rule | Superadmin | Owner | Property manager | Tenant |
|---|---|---|---|---|
| Portfolio records | View/create/edit/archive any. | View/edit own; cannot create/archive portfolio. | View own metadata; no portfolio mutation. | No portfolio pages. |
| Property hierarchy | Full. | Full inside own portfolio. | Assigned roots and descendants only. | No property directory. |
| Users | Any target except self-role mutation through normal CRUD. | Own portfolio managers and tenants; not owners/SA. | Assigned tenants only; not managers/owners/SA. | Own profile only. |
| Manager assignment | Any manager/root. | Own portfolio manager/root. | Cannot assign scope. | None. |
| Tenant, lease, payment, expense, maintenance, document records | Any. | Own portfolio. | Only records derived from assigned property roots/descendants. | Own lease, posted payments/receipts, safe documents, and own maintenance only. |
| Reports and exports | Global or filtered. | Own portfolio. | Assigned-property dataset. | Own statements/receipts only, no management report library. |
| CMS, wording, infrastructure, readiness, email logs, backups, showcase | Full. | None, except opening-data/readiness portfolio participation where explicitly exposed. | None. | None. |

Runtime authorization is intentionally layered. A visible navigation item does not grant access. Controllers/requests call feature access classes; queries then apply portfolio and assigned-property scope; presenters remove tenant-unsafe fields. Direct URL requests are checked again.

### 20.4 Table, pagination, and destructive-action rules

- Every serious management register uses server-side query state, not browser-only filtering.
- Page-size options are 10, 25, 50, and 100. There is no `All` option because unbounded property, payment, document, or audit history is unsafe on shared hosting. XLSX export is the supported complete filtered dataset.
- Search/filter/sort/pagination preserve URL state and use Inertia partial reloads rather than a full browser refresh.
- Row actions are permission- and state-aware. Mobile uses record cards below 992px; desktop uses the table columns listed in Section 9.
- General bulk delete is deliberately absent. Atomic opening import, controlled showcase purge, and backup pruning are the only current bulk-like operations.
- `DELETE` endpoints normally archive, void, terminate, cancel, detach, or prune according to domain rules. They do not silently erase financial/audit history.

## 21. Source Component And Screenshot Reference

No canonical screenshot pack is committed to the repository, so this report names the exact source components instead of pretending stale screenshots are authoritative.

| UI concern | Source ownership |
|---|---|
| Route-level Inertia entries | `resources/js/pages/*`; most files are intentionally thin feature adapters. |
| Main authenticated shell | `resources/js/layouts/admin-layout.tsx`, `resources/js/modules/shell/admin-sidebar.tsx`, `admin-topbar.tsx`, `account-menu.tsx`. |
| Sidebar access decisions | `resources/js/modules/shell/navigation-access.ts`; server access classes remain authoritative. |
| Global search/property context | `resources/js/modules/search/*`, `resources/js/modules/shell/property-context-*`. |
| Desktop/mobile tables | `resources/js/components/data-table/data-table.tsx`, `desktop-record-table.tsx`, `mobile-record-list.tsx`, `table-toolbar.tsx`, `table-pagination.tsx`. |
| Shared CRUD forms/details | `resources/js/components/resource-cycle/*`; route entries `pages/admin/resource-form.tsx` and `resource-show.tsx`. |
| Shared operational visual language | `resources/js/components/operations/workspace/*`: headers, panels, metrics, badges, actions. |
| Dashboard | `resources/js/modules/dashboard/*`. |
| Tenant portal | `resources/js/modules/tenant-portal/*`; thin entries under `resources/js/pages/admin/tenant-portal/*`. |
| Full search results | `resources/js/modules/search/results-page.tsx`; `/global-search` remains the topbar JSON contract. |
| Property pages | `resources/js/modules/assets/*`, including list, hierarchy, detail, and map/explorer features. |
| Tenant management/account statement | `resources/js/modules/tenants/*`. |
| Contract/collection/payment | `resources/js/modules/leases/*`, `lease-renewals/*`, `lease-move-outs/*`, `rent-collection/*`, `payments/*`. |
| Maintenance | `resources/js/modules/maintenance/*`, `maintenance-work-orders/*`, `maintenance-vendors/*`. |
| Reports | `resources/js/modules/reports/*`, `daily-operations-reports/*`. |
| CMS/public website | `resources/js/modules/cms/*`, `resources/js/modules/public-site/*`. |
| Media and notifications | `resources/js/modules/media/*`, `resources/js/modules/notifications/*`; media files remain permission-served rather than public raw paths. |
| System control | `resources/js/modules/system-readiness/*`, `infrastructure-settings/*`, `email-delivery/*`, `system-backups/*`, `showcase-data/*`, `wording/*`. |

The generic `ResourceFormShell` and `ResourceDetailShell` do not make the domain generic. Laravel presenters under `app/Modules/*/Presenters` define the actual fields, sections, tabs, actions, and safe payload for each resource.

## 22. Implemented, Operationally Blocked, And Not Implemented

| Capability | State | Practical meaning |
|---|---|---|
| Portfolio/property/unit/tenant/lease/payment/expense/maintenance/document operations | Implemented | Source, permissions, UI, exports, and automated coverage exist. |
| Owner, manager, tenant portal scoping | Implemented | Data is constrained by role, portfolio, assignment, and own-tenancy rules. |
| Responsive admin and tenant UI | Implemented | Desktop tables and mobile cards exist; pilot observation is still required. |
| English/Arabic/RTL | Implemented | UI, CMS, forms, reports, and generated documents support both languages. |
| SMTP email delivery | Operational dependency | Settings/logging/queue code exists; live mailbox receipt is not proven. |
| Scheduler and queued jobs | Operational dependency | Commands and readiness checks exist; Hostinger heartbeat/drain evidence is missing. |
| Real opening balances and portfolio data | Operational dependency | Import workflow exists; no approved real workbook has been reconciled. |
| WhatsApp messaging API | Not implemented | WhatsApp is only a recorded follow-up method. |
| Payment gateway/bank synchronization | Not implemented | Payments are entered manually. |
| Cryptographic e-signature | Not implemented | The system generates files and accepts signed PDF uploads. |
| Double-entry accounting/VAT filing | Not implemented | Finance is operational cash, due, arrears, expense, and net reporting. |
| Vendor/contractor portal | Not implemented | Managers control vendors and work orders internally. |
| Generic bulk editing/deletion | Not implemented by design | High-risk records require individual state-aware actions. |
| Native mobile application | Not implemented | Current product is a responsive Laravel/Inertia web application. |

Section 15 contains the detailed defects and debt. The blunt conclusion is unchanged: the system is broad enough for a controlled pilot. SMTP, scheduler evidence, reconciled real data, legal/business approval, and four-role user acceptance matter more than adding another module.
