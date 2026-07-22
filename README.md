# Property Management Control

Laravel 13 property management platform built with Inertia React, Bootstrap 5.3, MySQL-ready data models, and bilingual English/Arabic support.

## Stack

- Laravel 13
- Inertia.js + React 19 + TypeScript
- Bootstrap 5.3 with a white, vibrant custom theme
- MySQL in production, SQLite for local/testing
- Spatie Permission for roles and Spatie Activitylog for auditing
- DomPDF for contracts, statements, and receipts

## Implemented modules

- Role-aware dashboards for `superadmin`, `owner`, `property_manager`, and `tenant`
- Portfolio and user management with portfolio isolation
- Asset tree management for properties, buildings, floors, units, and spaces
- Tenant profiles, leases, installments, manual payments, and PDF outputs
- Maintenance request intake and expense tracking
- Superadmin CMS controls for public pages, sections, navigation, and media
- Modular public-site rendering with editable CMS content and a bilingual fallback catalog
- English/Arabic locale switching with RTL support for Arabic

## Local setup

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

## Local demo accounts

These accounts are created only by the local/demo seeders. Production accounts are created by the system owner inside the app and are not committed here.

- `superadmin@propertycontrol.test` / `password`
- `owner@propertycontrol.test` / `password`
- `manager@propertycontrol.test` / `password`
- `tenant@propertycontrol.test` / `password`

## Verification

```bash
php artisan migrate:fresh --seed
npm run types:check
npm run build
php -d memory_limit=1G artisan test
```

`php artisan test` requires a PHP runtime with the `xmlwriter` extension enabled.

## Deployment

Hostinger deployment notes live in [docs/hostinger-deploy.md](docs/hostinger-deploy.md). Production secrets are intentionally not committed.

The vertical feature-module rules and refactor checklist live in [docs/modular-refactor.md](docs/modular-refactor.md).
