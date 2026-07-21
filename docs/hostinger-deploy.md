# Hostinger Deployment Notes

These notes target `property.ahmaddalao.com` on Hostinger shared hosting.

## Before you deploy

- Set the website PHP version to `8.4` (the application requires PHP `8.4.1` or newer).
- Confirm the PHP `calendar` and `mbstring` extensions are enabled; bilingual PDF shaping requires both.
- Keep the repository as the source of truth. Do not commit `.env`, database passwords, FTP passwords, or generated secrets.
- Build assets before deployment if the Hostinger target does not have Node available.
- Take a database and private-storage backup before changing dependencies or running migrations.

## Recommended layout

Best option:

1. Deploy the Laravel app to a non-public directory on the hosting account.
2. Point the website document root to the app's `public/` directory.

If you must keep the current website root at `/home/u867436826/domains/ahmaddalao.com/public_html/property`, use Hostinger's Git deployment or FTP upload for the app files. The tracked root `index.php` and `.htaccess` are the shared-hosting compatibility shim: public assets are served from `public/`, application paths are denied, and all other requests enter Laravel. Keep both files synchronized with production.

## Production `.env`

Create the live `.env` on the server with production values such as:

```dotenv
APP_NAME="Property Management Control"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://property.ahmaddalao.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Riyadh

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=change-me
DB_USERNAME=change-me
DB_PASSWORD=change-me

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=change-me
MAIL_PORT=587
MAIL_USERNAME=change-me
MAIL_PASSWORD=change-me
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="property@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Use the real Hostinger database credentials in the server-side `.env`. Do not put them in git.
Configure a working Hostinger SMTP mailbox before enabling password recovery for users. `MAIL_MAILER=log` does not deliver reset links.

## Deploy steps

1. Upload or deploy the repository contents, including `vendor/` and `public/build/` if the target environment is not building dependencies itself. Run `composer install --no-dev --classmap-authoritative` on Hostinger whenever `composer.lock` changes.
2. Set correct file permissions for `storage/` and `bootstrap/cache/`.
3. Generate or copy the production `.env`.
4. Run:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder --force
php artisan storage:link || php artisan property:sync-public-storage
php artisan optimize:clear
php artisan optimize
php artisan property:sync-operational-statuses
```

5. Verify login, dashboard loading, PDF generation, uploads, and Arabic locale switching.

## Cron

Create a cron job that runs Laravel's scheduler every minute:

```bash
php /path/to/artisan schedule:run >> /dev/null 2>&1
```

The app schedules `queue:work --stop-when-empty` every minute and `property:sync-operational-statuses` daily. The scheduler is required for queued password-reset mail, showcase jobs, lease expiry, occupancy release, and overdue installment updates.

## First production checks

- `https://property.ahmaddalao.com/up`
- Superadmin login
- Asset creation
- Lease contract generation
- Payment posting and receipt download
- Tenant portal contract access
- Maintenance request submission
- Homepage English/Arabic toggle
- Password reset email delivery through production SMTP

Do not run `property:seed-demo-data` or generate showcase data in production unless production demo records are explicitly wanted. Lease clauses must be supplied from portfolio-approved legal wording; the application does not invent legal terms.
