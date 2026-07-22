<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use App\Modules\Cms\Support\CmsPageAttributes;
use App\Modules\Cms\Support\CmsPublicationPolicy;
use Illuminate\Support\Facades\DB;

final class UpdateCmsPage
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly CmsPageAttributes $attributes,
        private readonly CmsPublicationPolicy $publication,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, CmsPage $target, array $data): CmsPage
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target, $data): CmsPage {
            $page = CmsPage::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $validated = $this->input->page($data, $page);
            $payload = $this->attributes->forUpdate($page, $validated);
            $this->publication->ensurePageCanPublish($payload, $page);
            $this->publication->clearOtherHomepages($payload, $page);
            $page->update($payload);

            return $page->refresh();
        }, 3);
    }
}
