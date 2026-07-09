<?php

use App\Models\User;
use App\Services\LandingContentSeeder;
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

Schedule::command('queue:work --stop-when-empty --queue=default --tries=3 --timeout=90')
    ->everyMinute()
    ->withoutOverlapping();
