<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\CmsPage;
use App\Models\User;

class PlatformStatusQuery
{
    /** @return array<string, mixed>|null */
    public function forUser(User $user): ?array
    {
        if (! $user->hasRole('superadmin')) {
            return null;
        }

        return [
            'published' => CmsPage::query()->where('status', 'published')->count(),
            'draft' => CmsPage::query()->where('status', 'draft')->count(),
            'homepage' => CmsPage::query()->where('is_homepage', true)->value('title_en'),
        ];
    }
}
