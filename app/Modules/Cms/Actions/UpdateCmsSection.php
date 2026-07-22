<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use App\Modules\Cms\Support\CmsPublicationPolicy;
use Illuminate\Support\Facades\DB;

final class UpdateCmsSection
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly CmsPublicationPolicy $publication,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, CmsSection $target, array $data): CmsSection
    {
        $this->access->ensureAdmin($actor);
        $data = $this->input->section($data);

        return DB::transaction(function () use ($target, $data): CmsSection {
            $section = CmsSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $this->publication->ensureSectionUpdateCanPublish($section, $data);
            $section->update($data);

            if ($section->status === 'archived') {
                $section->pageSections()->update(['is_visible' => false]);
            }

            return $section->refresh();
        }, 3);
    }
}
