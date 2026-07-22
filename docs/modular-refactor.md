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

- Dashboard role selection lives in a 23-line `DashboardPresenter`; role-owned queries and presenters under `app/Modules/Dashboard` own scoped KPIs, activity, lease risk, setup guidance, CMS status, and tenant portal data.
- Dashboard frontend implementation lives in focused `operations`, `tenant`, and `shared` modules under `resources/js/modules/dashboard`; both role composers are under 35 lines.
- `resources/js/pages/dashboard.tsx` remains the stable Inertia page adapter, while a discriminated TypeScript contract prevents tenant and operations payloads from bleeding into each other.
- Admin navigation metadata now lives in `resources/js/modules/registry.ts`.
- Backend module names are documented in `app/Modules/ModuleRegistry.php`.
- Assets are the reference full-cycle module. `AssetController` is a 103-line HTTP adapter; queries, requests, transactions, forms, details, metadata, and hierarchy rules live under `app/Modules/Assets`.
- Reusable portfolio scoping, table query behavior, and resource payload helpers live under `app/Modules/Shared`.
- The Asset React workspace is split into a 42-line page composer plus module-owned filters, metrics, table, and contracts.
- `AssetModuleArchitectureTest` prevents query, validation, and database work from leaking back into the controller.
- Maintenance is the second complete vertical module. Its controller fell from 788 lines to a 106-line adapter; its former 241-line action service is a 32-line facade over locked create, update, and cancel actions plus a shared portfolio-reference guard.
- The maintenance internals are modular too: the former 310-line index query is a 72-line composer over scoped directory, SQL insight, and row-presentation units; the 230-line form presenter is a 25-line create/triage facade; and the 168-line detail presenter is a 33-line composer over an access-aware query, typed detail data, overview decisions, related updates, expenses, and audit history.
- The Maintenance React workspace is a 51-line page composer, while its former 176-line table is a 39-line composer over typed configuration, translated filters, explicit mobile cards, and focused cells. Queue rows no longer ship descriptions, internal notes, or update threads; tenant detail payloads omit internal notes, expenses, and audit history at the server boundary.
- `MaintenanceModuleArchitectureTest` caps each boundary. Feature and browser coverage exercise direct-action access, canonical lease aliases, scoped SQL metrics, posted-cost calculations, terminal cancellation, EN/AR forms and details, manager/tenant payload differences, mobile cards, accessibility, and zero overflow.
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
- The portfolio slice is internally modular as well: its former 388-line detail presenter is a 31-line composer over a typed detail payload, scoped query, action presenter, overview presenter, and related-record presenter. Its former 254-line index query is a 70-line composer over separate directory and aggregate-insight queries, while its 205-line React table is a 31-line composer over focused cell and configuration modules. `PortfolioModuleArchitectureTest` caps these boundaries and prevents SQL or row actions from leaking back into the composers.
- CMS is the tenth complete vertical module. Page, section, composition, navigation editing, forms, and the builder workspace live under `app/Modules/Cms`; the page controller is a 154-line admin route adapter and the builder entry is a 60-line composer.
- Media is the eleventh complete vertical module. Scoped directory queries, image validation, safe storage transitions, authorized responses, CMS usage detection, forms, details, and the reusable CMS picker live under `app/Modules/Media` and `resources/js/modules/media`; its controller is an 86-line adapter and its index is a 37-line composer.
- `MediaModuleArchitectureTest` guards the split. Feature coverage enforces portfolio isolation, private storage, real image validation, public/private disk transitions, CMS usage locks, portable public URLs, safe picker scope, and physical file deletion.
- Reports are the twelfth complete vertical module. `ReportController` fell from 493 lines to a 57-line adapter; validated filters, scoped calculations, saved-view permissions, page presentation, and `.xlsx` generation now live under `app/Modules/Reports`.
- The Report React workspace fell from an 838-line monolith to a 131-line composer with module-owned filters, tabs, KPI cards, collection/cost/operations panels, visuals, saved views, and contracts. Feature coverage rejects malformed dates and foreign portfolio filters, strips unsupported saved filters, enforces one personal default view, hides unauthorized delete actions, and verifies Arabic workbook output.
- Audit is the thirteenth complete vertical module. `AuditLogController` fell from 371 lines to a 34-line adapter; validated filters, portfolio/actor access, polymorphic subject mapping, activity presentation, scoped queries, and `.xlsx` generation now live under `app/Modules/Audit`.
- The Audit React workspace fell from a 271-line route page to a 35-line composer with module-owned metrics, filters, formatting, mobile cards, table columns, and contracts. Feature coverage enforces owner isolation, malformed-date rejection, accurate event facets, Arabic record labels, direct subject links, sensitive-key suppression, real XLSX output, zero horizontal overflow, and WCAG AA contrast.
- Global search and resource exports are cross-cutting infrastructure modules. `GlobalSearchController` fell from 389 lines to a 22-line adapter and `AdminExportController` fell from 479 lines to a 21-line adapter. Each feature owns its search source and workbook exporter, while the shared orchestrators only enforce limits, module availability, and response/file contracts.
- Index pages and exports now call the same module-owned filter query. This removes duplicate scope logic, makes relational searches consistent, and deleted the obsolete `BuildsAdminTables` controller trait.
- The global-search frontend is now a 48-line composer under `resources/js/modules/search`; request state, result grouping, desktop input, and the accessible mobile search sheet are separate focused units. The old shared component remains a one-line compatibility export.
- `SearchExportArchitectureTest` prevents the shared controllers and frontend entry from becoming monoliths again. Feature coverage enforces malformed-query rejection, Arabic expense search, portfolio isolation, tenant export denial, unknown-resource handling, relational-filter parity, and valid scoped XLSX output.
- Shared resource-cycle UI is now a compatibility barrel backed by focused header, form, input, action, spotlight, detail-tab, decision-card, related-record, document, and history modules. The former 1,047-line implementation is gone; the largest focused unit is 228 lines.
- Shared operational tables are now a compatibility barrel backed by separate query-state, toolbar, desktop-table, mobile-card, pagination, empty-state, showcase-badge, utility, and contract modules. Feature modules keep the same `DataTable`, `OperationsTable`, `exportUrl`, and type imports.
- `SharedFrontendArchitectureTest` caps every shared frontend unit at 250 lines or fewer, keeps both public wrappers at five lines or fewer, and prevents form, detail, query, desktop, and mobile responsibilities from collapsing back together.
- The property map is now an Assets-owned vertical slice. Its 406-line backend presenter is a 57-line orchestrator over scoped source/activity queries, hierarchy and coordinate helpers, localization, and focused asset/payload presenters. Its former 825-line React workspace is an 87-line composer, while the geographic renderer is 53 lines over a dedicated React lifecycle hook and Leaflet adapter.
- Property Map styling is a five-line facade over workspace, map, detail, directory, and responsive layers. `PropertyMapModuleArchitectureTest` caps backend, frontend, and CSS boundaries; keeps querying out of presenters; keeps Leaflet out of page composers; and prevents lifecycle code or monolithic styles from returning to the renderer.
- Page Wording is now a complete vertical control surface. Its controller fell from 127 lines to a 49-line adapter, the public catalog from 445 lines to a 73-line compatibility facade, the completeness service from 231 lines to an 85-line orchestrator, and the React entry from 568 lines to a 79-line composer.
- The wording editor is a 68-line shell over dedicated form-state, dialog-lifecycle, and form-rendering units. Its former 756-line stylesheet is a six-line facade over overview, workspace, catalog, translation-queue, editor, and responsive layers; retired inline-card CSS is removed. Translation defaults, documentation copy, override reads, transactional writes, placeholder protection, resolved dictionaries, cache invalidation, entry pagination, and four content-translation domains have separate owners. `WordingModuleArchitectureTest` caps backend, frontend, and CSS boundaries, and the wording cache is versioned so new EN/AR keys cannot be hidden by an older resolved dictionary.
- The role dashboard is now a complete vertical slice. Its 405-line backend presenter is a 23-line role selector over focused operations and tenant presenters plus seven scoped queries. The 406-line operations view and 299-line tenant view are now 34-line and 28-line composers over role-owned headers, metrics, action queues, financial panels, lease/documents, payment history, maintenance, and shared record components.
- Dashboard arrears now use a database aggregate instead of loading every lease installment into PHP. Unused all-lease chart payloads and the dead 246-line widget bundle are gone, owner/manager responses no longer receive global CMS status, tenant activity includes posted payments only, and tenant PDF metadata includes Arabic titles. `DashboardModuleArchitectureTest` protects every boundary.
- The shared admin shell is modular. Its former 349-line layout is a 38-line composer over focused drawer state, permission-aware navigation, sidebar, topbar, account menu, and password-notice modules under `resources/js/modules/shell`.
- The former 658-line shell stylesheet is a six-line import facade over bounded layout, sidebar, topbar, search, account, and responsive layers. Closed mobile navigation is removed from the keyboard order, desktop collapse preference persists safely, resize clears stale drawer state, active routes expose `aria-current`, RTL collapse direction is correct, and the account menu closes on Escape or outside interaction.
- `ShellModuleArchitectureTest` prevents state, access rules, rendering, and CSS from merging again. Browser coverage verifies the 64px topbar, body scroll lock, focus restoration, mobile-only drawer actions, desktop collapse persistence, role-specific menus, Arabic RTL, accessibility, and zero overflow.
- Profile is the fourteenth complete vertical slice. `ProfileController` is a 49-line route adapter over dedicated requests, actions, and a safe presenter; its former 529-line React page is a one-line route entry over focused identity, account-details, password, and access-context modules.
- `ProfileModuleArchitectureTest` protects the split. Feature coverage enforces field allowlists, trimming, localized language-transition feedback, current-password verification, temporary-password completion, and omission of password internals. Browser coverage verifies EN/AR content, one-column tablet/mobile forms, zero overflow, and rendered icons.
- The local Bootstrap Icon subset is now contract-tested against every icon used by React and Blade. A missing glyph fails architecture tests instead of shipping as a blank control.
- Documentation is the fifteenth complete vertical slice. `DocumentationController` fell from 133 lines to a 33-line adapter over role-aware index and guide presenters; configuration access, localization, module policy, route inference, and payload scoping live under `app/Modules/Documentation`.
- Its former 332-line index and 138-line guide page are one-line route entries over focused search, access, workflow, library, regulation, navigation, content, and related-guide components. Disabled portfolio modules now remove their guides, shortcuts, workflow steps, and direct URLs instead of exposing dead documentation.
- `DocumentationModuleArchitectureTest` protects the split. Feature and browser coverage enforce role/module scoping, payload allowlists, EN/AR guide content, direct links, searchable empty states, mobile guide navigation, zero overflow, and the absence of nested page landmarks. Documentation CSS is route-loaded as an 11.17 KB chunk instead of inflating the shared bundle.
- Showcase Data is the sixteenth complete vertical slice. Its former 902-line service is replaced by focused start, retry, refresh, failure, build, purge, and legacy-tagging actions plus separate generators, target/location support, metrics queries, and a page presenter. `ShowcaseDataController` is a 63-line HTTP adapter, the queue job only delegates, and the React entry is a 46-line composer.
- `ShowcaseDataModuleArchitectureTest` protects the split. Feature coverage proves read-only index behavior, start locking, paginated history, retry recovery, exact 40-building totals, valid terminated dates, PDF generation, map aggregates, idempotency, and confirmed dataset-only purge. Browser coverage verifies the collapsed generation plan, responsive cards, EN/AR rendering, zero overflow, body scroll lock, focus trapping, Escape dismissal, and focus restoration.
- Public Site is the seventeenth complete vertical slice. `PublicSiteController` is a 28-line adapter over published-page queries and presentation; landing defaults, idempotent seeding, and public navigation live under `app/Modules/PublicSite`. The former 401-line homepage fallback, 373-line renderer, and 135-line layout are thin compatibility entries over focused components in `resources/js/modules/public-site`.
- Public landing defaults have one source: `config/public-site.php`. Live content remains editable through CMS; the config is used for an empty-database fallback and by `property:seed-landing-content`. Do not add hardcoded fallback sections to React. The former 707-line public stylesheet is a six-line facade over six bounded layers, and architecture/browser coverage protects EN/AR parity, 64px mobile navigation, body lock, focus restoration, accessibility, and zero overflow.

## Resource Refactor Checklist

1. Preserve routes and response contracts with focused feature tests.
2. Move validation to module `Requests`.
3. Move mutations to a transactional `Action`.
4. Move index reads to a scoped `Query`.
5. Move form and detail payloads to `Presenters`.
6. Split the React page into module contracts and focused components.
7. Add an architecture guard, run PHPStan without new suppressions, then run the full browser cycle.

The vertical module refactor is complete for the current MVP surface. Further architecture work should be driven by measured defects or new product scope; the immediate priority is production onboarding, backup recovery, SMTP delivery, legal-template approval, and a controlled real-property pilot.

## Local Verification

The local shell may not include PHP on `PATH`, but Vite Wayfinder shells out to `php`. Use this PATH for local builds:

```bash
PATH="/opt/homebrew/bin:/Users/ahmaddalao/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin:$PATH" ./node_modules/.bin/vite build
```

Recommended local checks:

```bash
/opt/homebrew/bin/php artisan route:list --except-vendor
/opt/homebrew/bin/php -d memory_limit=1G artisan test
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
