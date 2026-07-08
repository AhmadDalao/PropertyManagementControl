<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

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

Schedule::command('queue:work --stop-when-empty --queue=default --tries=3 --timeout=90')
    ->everyMinute()
    ->withoutOverlapping();
