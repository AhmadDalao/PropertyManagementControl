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

For an exact release archive, do not let tar replace the live application
directory's permissions. Temporary staging directories created by `mktemp`
normally use mode `0700`; applying that mode to the web root makes LiteSpeed
return static `403`/`404` responses before Laravel runs. Extract with:

```bash
tar --no-overwrite-dir -xzf /home/<hostinger-account>/property-release.tar.gz \
    -C /home/<hostinger-account>/domains/ahmaddalao.com/public_html/property
chmod 755 /home/<hostinger-account>/domains/ahmaddalao.com/public_html/property
stat -c '%a' /home/<hostinger-account>/domains/ahmaddalao.com/public_html/property
```

The final command must print `755`. Keep the application in maintenance mode
during extraction and always register a cleanup path that runs
`php artisan up` when a release command fails.

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

The Composer lifecycle clears Laravel's generated package manifest before rebuilding the autoloader. This prevents development-only providers from a previous local build being loaded during a production `--no-dev` deployment. Do not bypass Composer scripts when preparing a release.

For the FTP fallback, never upload `vendor/composer/` by itself. Composer's
generated loader files and `vendor/autoload.php` share one initialization ID;
mixing releases takes the entire site down before Laravel can log the error. If
dependencies did not change, preserve the known-working production `vendor/`
tree. If they did change, upload a complete production `vendor/` directory as
one release and switch the directory atomically.

Always synchronize `routes/` and remove any old
`bootstrap/cache/routes-v*.php` file before marking an FTP release complete.
A stale route cache can make deployed controllers and links return 404 or 500
even when `routes/web.php` is correct. Update `storage/app/.deployed-revision`
only after the manifest, routes, and cache cleanup are complete.

6. Require `https://property.ahmaddalao.com/up` to return HTTP `200` before running the authenticated smoke cycle. Then verify login, dashboard loading, PDF generation, uploads, and Arabic locale switching.

## Cron

In hPanel, open **Websites → Dashboard → Advanced → Cron Jobs**, choose
**Custom**, and set the schedule to every minute (`* * * * *`). Paste this
command into **Command to Run**:

```bash
/opt/alt/php84/usr/bin/php /home/u867436826/domains/ahmaddalao.com/public_html/property/artisan schedule:run
```

Do not add `>> /dev/null 2>&1` to the hPanel field. Hostinger rejects those
special characters unless a separate shell wrapper is created, and the direct
command does not need one. Hostinger cron schedules use UTC.

Do not use Hostinger's unqualified `php` command: its shared-hosting CLI may resolve to an older PHP release than the website. Set `SCHEDULER_PHP_BINARY` when the account uses a different verified PHP 8.4 path. The app records a scheduler heartbeat before running `queue:work --stop-when-empty` every minute and runs `property:sync-operational-statuses` daily. The scheduler is required for queued password-reset mail, showcase jobs, lease expiry, occupancy release, and overdue installment updates. `/system/readiness` requires three distinct heartbeat samples spanning at least 90 seconds, so a manual deployment command cannot make cron look healthy. Wait three minutes after saving the hPanel job, then refresh the readiness page.

Shared hosting can terminate a worker without releasing Laravel's overlap mutex. The heartbeat lock expires after 5 minutes, the queue-worker lock after 10 minutes, and the daily status-sync lock after 120 minutes. If a killed worker left an older lock from a previous release, run `php artisan schedule:clear-cache` once, then confirm the queue count decreases in `/system/readiness`.

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
- `/system/readiness` automatic checks, then record backup, restore, legal, opening-data, retention, and pilot evidence there

Do not run `property:seed-demo-data` or generate showcase data in production unless production demo records are explicitly wanted. Lease clauses must be supplied from portfolio-approved legal wording; the application does not invent legal terms.
