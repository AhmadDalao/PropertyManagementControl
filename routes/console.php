<?php

use App\Models\User;
use App\Services\LandingContentSeeder;
use App\Services\ShowcaseDatasetService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\PermissionRegistrar;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('property:sync-public-storage', function () {
    $source = storage_path('app/public');
    $destination = public_path('storage');

    if (! File::exists($source)) {
        $this->warn("Source directory [{$source}] does not exist.");

        return;
    }

    File::ensureDirectoryExists($destination);
    File::copyDirectory($source, $destination);

    $this->info("Copied public storage files to [{$destination}].");
})->purpose('Mirror storage/app/public into public/storage when symlinks are unavailable.');

Artisan::command('property:ensure-superadmin {email} {--password=} {--name=System Owner}', function (string $email) {
    $password = $this->option('password');
    $name = $this->option('name') ?: 'System Owner';
    $userExists = User::query()->where('email', $email)->exists();

    if (! $userExists && (! is_string($password) || $password === '')) {
        $this->error('A password is required when creating a new superadmin.');

        return 1;
    }

    Artisan::call('db:seed', [
        '--class' => RolesAndPermissionsSeeder::class,
        '--force' => true,
    ]);

    $user = DB::transaction(function () use ($email, $name, $password) {
        $attributes = [
            'portfolio_id' => null,
            'name' => $name,
            'preferred_locale' => 'en',
            'status' => 'active',
            'force_password_reset' => false,
            'email_verified_at' => now(),
        ];

        if (is_string($password) && $password !== '') {
            $attributes['password'] = Hash::make($password);
        }

        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            $attributes,
        );

        DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', User::class)
            ->delete();

        $user->syncRoles(['superadmin']);

        return $user;
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->info("Superadmin [{$user->email}] is ready with role type [{$user->getMorphClass()}].");

    return 0;
})->purpose('Create or repair the global superadmin account.');

Artisan::command('property:seed-landing-content', function () {
    $result = app(LandingContentSeeder::class)->seed();

    $this->info("Landing page [{$result['page_id']}] seeded with {$result['sections']} sections and {$result['navigation_items']} navigation items.");

    return 0;
})->purpose('Seed editable public landing page CMS content.');

Artisan::command('property:seed-demo-data {--fresh-demo : Rebuild the local database before seeding demo data}', function () {
    if (app()->environment('production')) {
        $this->error('Demo data seeding is blocked in production.');

        return 1;
    }

    if (! $this->option('fresh-demo')) {
        $this->error('Use --fresh-demo so the demo dataset starts from a clean local database.');

        return 1;
    }

    Artisan::call('migrate:fresh', [
        '--seed' => true,
        '--force' => true,
    ]);

    $this->info(Artisan::output());
    $this->info('Local property demo data is ready.');
    $this->newLine();
    $this->line('Local demo accounts, all using password [password]:');
    $this->line('  superadmin@propertycontrol.test');
    $this->line('  owner@propertycontrol.test');
    $this->line('  manager@propertycontrol.test');
    $this->line('  tenant@propertycontrol.test');
    $this->line('  owner.jeddah@propertycontrol.test');
    $this->line('  manager.jeddah@propertycontrol.test');
    $this->line('  tenant.jeddah@propertycontrol.test');
    $this->newLine();
    $this->line('Open /dashboard, /documentation, /assets, /leases, /payments, and /maintenance-requests to review the full cycle.');

    return 0;
})->purpose('Rebuild the local/staging database with rich demo property data. Blocked in production.');

Artisan::command('property:seed-showcase-data {--confirm-production : Allow tagged showcase generation in production}', function (ShowcaseDatasetService $service) {
    if (app()->environment('production') && ! $this->option('confirm-production')) {
        $this->error('Production showcase generation needs --confirm-production because it changes live totals until purged.');

        return 1;
    }

    Artisan::call('db:seed', [
        '--class' => RolesAndPermissionsSeeder::class,
        '--force' => true,
    ]);

    $superadmin = User::role('superadmin')->oldest('id')->first();

    if (! $superadmin) {
        $this->error('Create a superadmin before generating showcase data.');

        return 1;
    }

    $dataset = $service->start($superadmin);
    $this->info("Showcase dataset [{$dataset->key}] queued.");
    $this->line('Run the database queue or wait for the Hostinger scheduler to process 40 building jobs.');

    return 0;
})->purpose('Queue a tagged, retryable 40-building showcase dataset.');

Schedule::command('queue:work --stop-when-empty --queue=default --tries=3 --timeout=90')
    ->everyMinute()
    ->withoutOverlapping();
