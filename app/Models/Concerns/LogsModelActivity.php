<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsModelActivity
{
    use LogsActivity;

    /**
     * Configure activity log defaults for domain models.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->logExcept([
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'email_verified_at',
                'last_login_at',
            ])
            ->dontLogEmptyChanges();
    }
}
