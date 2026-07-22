<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use App\Modules\Cms\Support\CmsPageSectionOrder;
use Illuminate\Support\Facades\DB;

final class ReorderCmsPageSections
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly CmsPageSectionOrder $order,
    ) {}

    /** @param array<int, int> $orderedIds */
    public function handle(User $actor, CmsPage $target, array $orderedIds): void
    {
        $this->access->ensureAdmin($actor);
        $orderedIds = $this->input->reorder($orderedIds);

        DB::transaction(function () use ($target, $orderedIds): void {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $this->order->apply($page, $orderedIds);
        }, 3);
    }
}
