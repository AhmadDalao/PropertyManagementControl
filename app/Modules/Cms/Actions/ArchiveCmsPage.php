<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;

final class ArchiveCmsPage
{
    public function __construct(private readonly CmsAccess $access) {}

    public function handle(User $actor, CmsPage $target): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target): CmsPage {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $page->update([
                'status' => 'archived',
                'is_visible' => false,
                'is_homepage' => false,
                'published_at' => null,
            ]);

            return $page->refresh();
        }, 3);
    }
}
