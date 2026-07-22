<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\User;

final class ComposeCmsPage
{
    public function __construct(
        private readonly AttachCmsPageSection $attach,
        private readonly UpdateCmsPageSection $update,
        private readonly ReorderCmsPageSections $reorder,
        private readonly RemoveCmsPageSection $remove,
    ) {}

    /** @param array<string, mixed> $data */
    public function attach(User $actor, CmsPage $target, array $data): CmsPageSection
    {
        return $this->attach->handle($actor, $target, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, CmsPageSection $target, array $data): CmsPageSection
    {
        return $this->update->handle($actor, $target, $data);
    }

    /** @param array<int, int> $orderedIds */
    public function reorder(User $actor, CmsPage $target, array $orderedIds): void
    {
        $this->reorder->handle($actor, $target, $orderedIds);
    }

    public function remove(User $actor, CmsPageSection $target): CmsPage
    {
        return $this->remove->handle($actor, $target);
    }
}
