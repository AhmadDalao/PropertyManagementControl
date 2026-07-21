<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;

class ManageCmsSections
{
    public function __construct(private readonly CmsAccess $access) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): CmsSection
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(fn (): CmsSection => CmsSection::query()->create($data));
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, CmsSection $target, array $data): CmsSection
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target, $data): CmsSection {
            $section = CmsSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $section->update($data);

            if ($section->status === 'archived') {
                $section->pageSections()->update(['is_visible' => false]);
            }

            return $section->refresh();
        });
    }

    public function archive(User $actor, CmsSection $target): CmsSection
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target): CmsSection {
            $section = CmsSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $section->update(['status' => 'archived']);
            $section->pageSections()->update(['is_visible' => false]);

            return $section->refresh();
        });
    }
}
