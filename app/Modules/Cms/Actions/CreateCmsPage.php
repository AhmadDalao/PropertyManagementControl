<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use App\Modules\Cms\Support\CmsPageAttributes;
use App\Modules\Cms\Support\CmsPublicationPolicy;
use Illuminate\Support\Facades\DB;

final class CreateCmsPage
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly CmsPageAttributes $attributes,
        private readonly CmsPublicationPolicy $publication,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): CmsPage
    {
        $this->access->ensureAdmin($actor);
        $data = $this->input->page($data);

        return DB::transaction(function () use ($data): CmsPage {
            $payload = $this->attributes->forCreate($data);
            $this->publication->ensurePageCanPublish($payload);
            $this->publication->clearOtherHomepages($payload);

            return CmsPage::query()->create($payload);
        }, 3);
    }
}
