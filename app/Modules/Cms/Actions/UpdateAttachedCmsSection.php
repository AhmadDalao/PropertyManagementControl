<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\User;

final class UpdateAttachedCmsSection
{
    public function __construct(private readonly ManageCmsSections $sections) {}

    /** @param array<string, mixed> $data */
    public function handle(
        User $actor,
        CmsPage $page,
        CmsSection $section,
        array $data,
    ): CmsSection {
        abort_unless(
            $page->pageSections()
                ->where('cms_section_id', $section->id)
                ->exists(),
            404,
        );

        return $this->sections->update($actor, $section, $data);
    }
}
