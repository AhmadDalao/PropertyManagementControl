# Modular Refactor Notes

This app stays Laravel + Inertia React. The refactor direction is vertical modules, not a database rewrite.

## Module Boundaries

- Backend module logic belongs in `app/Modules/{ModuleName}`.
- Controllers stay thin: authorize/request in, delegate to module query/action/presenter, return Inertia/redirect.
- Frontend module logic belongs in `resources/js/modules/{module}`.
- Inertia pages under `resources/js/pages` should be route adapters, not thousand-line products.
- Shared UI primitives stay in `resources/js/components` only when they are truly cross-module.
- Module-owned widgets, metrics, local types, and view helpers stay inside that module folder.

## Current First Slice

- Dashboard backend data aggregation now lives in `app/Modules/Dashboard/DashboardPresenter.php`.
- Dashboard frontend implementation now lives in `resources/js/modules/dashboard`.
- `resources/js/pages/dashboard.tsx` remains as the stable Inertia page adapter.
- Admin navigation metadata now lives in `resources/js/modules/registry.ts`.
- Backend module names are documented in `app/Modules/ModuleRegistry.php`.

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
