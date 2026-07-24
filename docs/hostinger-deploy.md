# Hostinger Deployment Notes

These notes target `property.ahmaddalao.com` on Hostinger shared hosting.

## Before you deploy

- Set the website PHP version to `8.4` (the application requires PHP `8.4.1` or newer).
- Confirm the PHP `calendar` and `mbstring` extensions are enabled; bilingual PDF shaping requires both.
- Keep the repository as the source of truth. Never commit `.env`, database credentials, FTP credentials, or generated secrets.
- Build assets before deployment if the Hostinger target does not have Node available.
- Take a database and private-storage backup before changing dependencies or running migrations.

## Recommended layout

Best option:

1. Deploy the Laravel app to a non-public directory on the hosting account.
2. Point the website document root to the app's `public/` directory.

If the website root must stay at `/home/<hostinger-account>/domains/ahmaddalao.com/public_html/property`, use Hostinger Git deployment or an exact release upload. The tracked root `index.php` and `.htaccess` are the shared-hosting compatibility shim: public assets are served from `public/`, application paths are denied, and all other requests enter Laravel. Keep both files synchronized with production.

## Production `.env`

Create the live `.env` only on the server. Copy database and SMTP values from the Hostinger control panel:

```dotenv
APP_NAME="Property Management Control"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://property.ahmaddalao.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Riyadh

DB_CONNECTION=mysql
DB_HOST=<hostinger-database-host>
DB_PORT=3306
DB_DATABASE=<hostinger-database-name>
DB_USERNAME=<hostinger-database-user>
DB_PASSWORD=<hostinger-database-password>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=<hostinger-smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<hostinger-mailbox>
MAIL_PASSWORD=<hostinger-mailbox-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=<hostinger-mailbox>
MAIL_FROM_NAME="${APP_NAME}"
```

Configure a working Hostinger SMTP mailbox before enabling password recovery for users. `MAIL_MAILER=log` does not deliver reset links.

## Deploy steps

1. Upload or deploy the repository contents, including `vendor/` and `public/build/` when the target does not build dependencies itself.
2. Rebuild Composer's production autoloader after every PHP source deployment. The production autoloader is authoritative, so newly added application classes otherwise cause HTTP 500 responses.
3. Set correct file permissions for `storage/` and `bootstrap/cache/`.
4. Create the production `.env` and run `php artisan key:generate --force` only on the first deployment. Never rotate `APP_KEY` during a routine release.
5. Run on every deployment:

```bash
composer install --no-dev --classmap-authoritative --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan property:sync-operational-statuses
```

If dependencies are uploaded instead of installed on Hostinger, run `composer dump-autoload --no-dev --classmap-authoritative --no-interaction` after the upload. Run the permissions seeder and storage-link command only during initial setup or when their configuration changes.

6. Verify login, dashboard loading, PDF generation, uploads, and Arabic locale switching.

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
