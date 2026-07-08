# Hostinger Deployment Notes

These notes target `property.ahmaddalao.com` on Hostinger shared hosting.

## Before you deploy

- Set the website PHP version to `8.3` or `8.4`.
- Keep the repository as the source of truth. Do not commit `.env`, database passwords, FTP passwords, or generated secrets.
- Build assets before deployment if the Hostinger target does not have Node available.

## Recommended layout

Best option:

1. Deploy the Laravel app to a non-public directory on the hosting account.
2. Point the website document root to the app's `public/` directory.

If you must keep the current website root at `/home/u867436826/domains/ahmaddalao.com/public_html/property`, use Hostinger's Git deployment or FTP upload for the app files and make sure the web-facing directory serves Laravel's `public/` entrypoint. Do not dump the whole app into a public web root without accounting for the `public/` folder, because that is how you leak framework files.

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
```

Use the real Hostinger database credentials in the server-side `.env`. Do not put them in git.

## Deploy steps

1. Upload or deploy the repository contents, including `vendor/` and `public/build/` if the target environment is not building dependencies itself.
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
```

5. Verify login, dashboard loading, PDF generation, uploads, and Arabic locale switching.

## Cron

Create a cron job that runs Laravel's scheduler every minute:

```bash
php /path/to/artisan schedule:run >> /dev/null 2>&1
```

The app schedules `queue:work --stop-when-empty`, which is a sane shared-hosting compromise for database-backed queues.

## First production checks

- `https://property.ahmaddalao.com/up`
- Superadmin login
- Asset creation
- Lease contract generation
- Payment posting and receipt download
- Tenant portal contract access
- Maintenance request submission
- Homepage English/Arabic toggle
