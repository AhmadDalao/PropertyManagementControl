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

## Resource Refactor Checklist

1. Preserve routes and response contracts with focused feature tests.
2. Move validation to module `Requests`.
3. Move mutations to a transactional `Action`.
4. Move index reads to a scoped `Query`.
5. Move form and detail payloads to `Presenters`.
6. Split the React page into module contracts and focused components.
7. Add an architecture guard, run PHPStan without new suppressions, then run the full browser cycle.

Next backend targets by risk and size are Payments and Documents.

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
