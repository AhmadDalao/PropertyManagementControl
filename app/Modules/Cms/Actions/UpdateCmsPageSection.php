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

final class UpdateCmsPageSection
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly CmsPublicationPolicy $publication,
        private readonly CmsPageSectionOrder $order,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, CmsPageSection $target, array $data): CmsPageSection
    {
        $this->access->ensureAdmin($actor);
        $data = $this->input->pageSection($data);

        return DB::transaction(function () use ($target, $data): CmsPageSection {
            $pageSection = CmsPageSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $page = CmsPage::query()->lockForUpdate()->whereKey($pageSection->cms_page_id)->firstOrFail();
            $section = CmsSection::query()->lockForUpdate()->whereKey($pageSection->cms_section_id)->firstOrFail();
            $isVisible = array_key_exists('is_visible', $data)
                ? (bool) $data['is_visible']
                : $pageSection->is_visible;
            $this->publication->ensureSectionCanRender($page, $section, $isVisible);
            $pageSection->update([
                'sort_order' => $data['sort_order'] ?? $pageSection->sort_order,
                'is_visible' => $isVisible,
                'settings_json' => array_key_exists('settings_json', $data)
                    ? $data['settings_json']
                    : $pageSection->settings_json,
            ]);
            $this->order->normalize($page);

            return $pageSection->refresh();
        }, 3);
    }
}
