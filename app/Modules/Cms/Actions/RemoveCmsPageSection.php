<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsPageSectionOrder;
use Illuminate\Support\Facades\DB;

final class RemoveCmsPageSection
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsPageSectionOrder $order,
    ) {}

    public function handle(User $actor, CmsPageSection $target): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target): CmsPage {
            $pageSection = CmsPageSection::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $page = CmsPage::query()->lockForUpdate()->whereKey($pageSection->cms_page_id)->firstOrFail();
            $pageSection->delete();
            $this->order->normalize($page);

            return $page;
        }, 3);
    }
}
