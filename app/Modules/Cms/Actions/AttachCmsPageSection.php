<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use App\Modules\Cms\Support\CmsPageSectionOrder;
use App\Modules\Cms\Support\CmsPublicationPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttachCmsPageSection
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly CmsPublicationPolicy $publication,
        private readonly CmsPageSectionOrder $order,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, CmsPage $target, array $data): CmsPageSection
    {
        $this->access->ensureAdmin($actor);
        $data = $this->input->attachment($data);

        return DB::transaction(function () use ($target, $data): CmsPageSection {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $section = CmsSection::query()->lockForUpdate()->whereKey($data['cms_section_id'])->firstOrFail();

            if ($section->status === 'archived') {
                throw ValidationException::withMessages([
                    'cms_section_id' => trans('app.errors.cms_archived_section'),
                ]);
            }

            $isVisible = (bool) ($data['is_visible'] ?? true);
            $this->publication->ensureSectionCanRender($page, $section, $isVisible);
            $pageSection = CmsPageSection::query()->updateOrCreate(
                [
                    'cms_page_id' => $page->id,
                    'cms_section_id' => $section->id,
                ],
                [
                    'sort_order' => $data['sort_order'] ?? ((int) $page->pageSections()->max('sort_order') + 1),
                    'is_visible' => $isVisible,
                ],
            );
            $this->order->normalize($page);

            return $pageSection->refresh();
        }, 3);
    }
}
