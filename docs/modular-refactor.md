# Modular Refactor Notes

This app stays Laravel + Inertia React. The refactor direction is vertical modules, not a database rewrite.

## Module Boundaries

- Backend module logic belongs in `app/Modules/{ModuleName}`.
- Controllers stay thin: authorize/request in, delegate to module query/action/presenter, return Inertia/redirect.
- Frontend module logic belongs in `resources/js/modules/{module}`.
- Inertia pages under `resources/js/pages` should be route adapters, not thousand-line products.
- Shared UI primitives stay in `resources/js/components` only when they are truly cross-module.
- Module-owned widgets, metrics, local types, and view helpers stay inside that module folder.

## Dependency Rules

1. Controllers may authorize, delegate, render, and redirect. They do not build queries, validate field lists, mutate related records, or assemble page payloads.
2. `Queries` read scoped data; `Actions` perform transactions; `Requests` own validation and request authorization; `Presenters` shape Inertia payloads; `Support` contains module-specific pure helpers.
3. A feature module may depend on models and `app/Modules/Shared`. Shared code must not depend on a feature module.
4. Frontend route entries compose module components. Table columns, filters, metrics, types, and record-specific helpers stay inside their feature module.
5. Do not create a shared abstraction until two modules need the same behavior. Folder theater is still theater.

## Current Slices

- Dashboard backend data aggregation now lives in `app/Modules/Dashboard/DashboardPresenter.php`.
- Dashboard frontend implementation now lives in `resources/js/modules/dashboard`.
- `resources/js/pages/dashboard.tsx` remains as the stable Inertia page adapter.
- Admin navigation metadata now lives in `resources/js/modules/registry.ts`.
- Backend module names are documented in `app/Modules/ModuleRegistry.php`.
- Assets are the reference full-cycle module. `AssetController` is a 103-line HTTP adapter; queries, requests, transactions, forms, details, metadata, and hierarchy rules live under `app/Modules/Assets`.
- Reusable portfolio scoping, table query behavior, and resource payload helpers live under `app/Modules/Shared`.
- The Asset React workspace is split into a 42-line page composer plus module-owned filters, metrics, table, and contracts.
- `AssetModuleArchitectureTest` prevents query, validation, and database work from leaking back into the controller.
- Maintenance is the second complete vertical module. Its controller fell from 788 lines to a 106-line adapter; scoped reads, transactions, SLA scheduling, access rules, forms, details, and options now live under `app/Modules/Maintenance`.
- The Maintenance React workspace is a 51-line composer with module-owned filters, metrics, table, and contracts. Tenant presenters explicitly remove internal comments and owner cost data.
- `MaintenanceModuleArchitectureTest` guards the split, while feature tests exercise direct action access, morph-alias leases, and tenant-safe payloads.
- Leases are the third complete vertical module. `LeaseController` fell from 693 lines to a 117-line adapter; scoped reads, validation, lifecycle transactions, PDF generation, forms, details, access rules, and options now live under `app/Modules/Leases`.
- The Lease React workspace is a 42-line composer with module-owned filters, metrics, table, and contracts. Index payloads no longer ship full installment or document collections, and tenant details omit internal notes, admin actions, internal documents, and audit history.
- `LeaseModuleArchitectureTest` guards the split. Feature coverage enforces canonical morph aliases, active-lease exclusivity, asset occupancy synchronization, PDF-only signed contracts, and direct-action portfolio isolation.
- Payments are the fourth complete vertical module. `PaymentController` fell from 562 lines to a 98-line adapter; scoped reads, validation, allocation transactions, receipt generation, forms, details, access rules, and options now live under `app/Modules/Payments`.
- The Payment React workspace is a 42-line composer with module-owned filters, metrics, table, and contracts. Payment portfolio, tenant, and currency values are derived from the selected lease, index payloads use SQL aggregates instead of full allocation collections, and tenant details omit notes, internal documents, admin links, and audit history.
- `PaymentModuleArchitectureTest` guards the split. Feature coverage enforces direct-action portfolio isolation, locked void transitions, draft-lease rejection, safe receipt filenames, generated-file replacement, Arabic lease labels, and tenant-safe payloads.
- Lease installment scheduling and payment allocation are separate actions. Shared private PDF replacement now lives in `app/Modules/Shared/PrivatePdfDocuments.php` for both contracts and receipts.
- Documents are the fifth complete vertical module. `DocumentController` fell from 509 lines to a 95-line adapter; scoped reads, PDF validation, attachment resolution, visibility rules, downloads, forms, and details now live under `app/Modules/Documents`.
- The Document React workspace is a 37-line composer with module-owned filters, metrics, table, and contracts. Index payloads expose a small attachment summary instead of full polymorphic records and no longer preload three unused option collections.
- `DocumentModuleArchitectureTest` guards the split. Feature coverage enforces immutable attachments, portfolio isolation, real PDF signatures, tenant-safe lease and receipt downloads, missing-file handling, Arabic wording, supported page sizes, filtered XLSX exports, and the portal-visibility migration.
- `documents.is_public` now means tenant-portal visibility, not unauthenticated public access. Only approved lease documents and payment receipts can use it; all files remain on private storage and every download is authorized.
- Tenants are the sixth complete vertical module. `TenantController` fell from 434 lines to an 83-line adapter; scoped reads, validation, account synchronization, archive guards, forms, details, access rules, and options now live under `app/Modules/Tenants`.
- The Tenant React workspace is a 42-line composer with module-owned filters, metrics, table, and contracts. Index payloads use SQL aggregates, detail history is bounded, and explicit financial tabs avoid locale-dependent content guessing.
- `TenantModuleArchitectureTest` guards the split. Feature coverage enforces portfolio isolation, strict profile values, inactive-portfolio rejection, transactional portal-account synchronization, password reset, orphan-login recovery, active-lease archive guards, tenant-role denial, bounded history, and Arabic forms/details.
- Expenses are the seventh complete vertical module. `ExpenseEntryController` fell from 412 lines to an 81-line adapter; scoped reads, strict validation, locked mutations, form references, financial summaries, access rules, and detail payloads now live under `app/Modules/Expenses`.
- The Expense React workspace is a 45-line composer with module-owned filters, metrics, table, and contracts. Index payloads no longer preload unbounded asset and maintenance collections, and mixed-currency scopes no longer display a false combined total.
- `ExpenseModuleArchitectureTest` guards the split. Feature coverage enforces portfolio isolation across pages and XLSX exports, active-portfolio creation, portfolio-derived currency, reference consistency, terminal voiding, legacy/showcase category compatibility, tenant denial, and Arabic forms/details.
- Users are the eighth complete vertical module. Directory scope, role rules, account mutations, forms, details, exports, and global-search links share the same `Users` access boundary; its controller is an 85-line adapter and its React index is a 37-line composer.
- Portfolios are the ninth complete vertical module. Account ownership, module visibility, archive rules, mixed-currency summaries, forms, details, queries, and mutations live under `app/Modules/Portfolios`; its controller is a 91-line adapter and its React index is a 44-line composer.
- CMS is the tenth complete vertical module. Page, section, composition, navigation, public rendering, form, and workspace responsibilities live under `app/Modules/Cms`; the page controller is a 166-line route adapter and the builder entry is a 60-line composer.
- Media is the eleventh complete vertical module. Scoped directory queries, image validation, safe storage transitions, authorized responses, CMS usage detection, forms, details, and the reusable CMS picker live under `app/Modules/Media` and `resources/js/modules/media`; its controller is an 86-line adapter and its index is a 37-line composer.
- `MediaModuleArchitectureTest` guards the split. Feature coverage enforces portfolio isolation, private storage, real image validation, public/private disk transitions, CMS usage locks, portable public URLs, safe picker scope, and physical file deletion.
- Reports are the twelfth complete vertical module. `ReportController` fell from 493 lines to a 57-line adapter; validated filters, scoped calculations, saved-view permissions, page presentation, and `.xlsx` generation now live under `app/Modules/Reports`.
- The Report React workspace fell from an 838-line monolith to a 131-line composer with module-owned filters, tabs, KPI cards, collection/cost/operations panels, visuals, saved views, and contracts. Feature coverage rejects malformed dates and foreign portfolio filters, strips unsupported saved filters, enforces one personal default view, hides unauthorized delete actions, and verifies Arabic workbook output.
- Audit is the thirteenth complete vertical module. `AuditLogController` fell from 371 lines to a 34-line adapter; validated filters, portfolio/actor access, polymorphic subject mapping, activity presentation, scoped queries, and `.xlsx` generation now live under `app/Modules/Audit`.
- The Audit React workspace fell from a 271-line route page to a 35-line composer with module-owned metrics, filters, formatting, mobile cards, table columns, and contracts. Feature coverage enforces owner isolation, malformed-date rejection, accurate event facets, Arabic record labels, direct subject links, sensitive-key suppression, real XLSX output, zero horizontal overflow, and WCAG AA contrast.

## Resource Refactor Checklist

1. Preserve routes and response contracts with focused feature tests.
2. Move validation to module `Requests`.
3. Move mutations to a transactional `Action`.
4. Move index reads to a scoped `Query`.
5. Move form and detail payloads to `Presenters`.
6. Split the React page into module contracts and focused components.
7. Add an architecture guard, run PHPStan without new suppressions, then run the full browser cycle.

The next backend target by risk is consolidation of shared global-search and export contracts, followed by removal of the remaining controller table/resource traits once their last consumers have moved into modules.

## Local Verification

The local shell may not include PHP on `PATH`, but Vite Wayfinder shells out to `php`. Use this PATH for local builds:

```bash
PATH="/opt/homebrew/bin:/Users/ahmaddalao/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin:$PATH" ./node_modules/.bin/vite build
```

Recommended local checks:

```bash
/opt/homebrew/bin/php artisan route:list --except-vendor
/opt/homebrew/bin/php artisan test
PATH="/Users/ahmaddalao/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin:$PATH" ./node_modules/.bin/tsc --noEmit
PATH="/Users/ahmaddalao/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin:$PATH" ./node_modules/.bin/eslint .
PATH="/Users/ahmaddalao/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin:$PATH" ./node_modules/.bin/prettier --check resources/
PATH="/opt/homebrew/bin:/Users/ahmaddalao/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin:$PATH" ./node_modules/.bin/vite build
```

Non-destructive production smoke check:

```bash
/opt/homebrew/bin/php tests/live_property_smoke.php --base-url=https://property.ahmaddalao.com --email=admin@example.com --password='secret'
```

Pass real credentials from the shell when running it. Do not commit them.
