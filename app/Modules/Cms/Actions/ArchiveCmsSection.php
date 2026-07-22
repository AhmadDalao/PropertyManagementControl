<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;

final class ArchiveCmsSection
{
    public function __construct(private readonly CmsAccess $access) {}

    public function handle(User $actor, CmsSection $target): CmsSection
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target): CmsSection {
            $section = CmsSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $section->update(['status' => 'archived']);
            $section->pageSections()->update(['is_visible' => false]);

            return $section->refresh();
        }, 3);
    }
}
