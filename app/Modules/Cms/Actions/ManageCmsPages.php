<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\User;

final class ManageCmsPages
{
    public function __construct(
        private readonly CreateCmsPage $create,
        private readonly UpdateCmsPage $update,
        private readonly ArchiveCmsPage $archive,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): CmsPage
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, CmsPage $target, array $data): CmsPage
    {
        return $this->update->handle($actor, $target, $data);
    }

    public function archive(User $actor, CmsPage $target): CmsPage
    {
        return $this->archive->handle($actor, $target);
    }
}
