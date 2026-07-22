<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsSection;
use App\Models\User;

final class ManageCmsSections
{
    public function __construct(
        private readonly CreateCmsSection $create,
        private readonly UpdateCmsSection $update,
        private readonly ArchiveCmsSection $archive,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): CmsSection
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, CmsSection $target, array $data): CmsSection
    {
        return $this->update->handle($actor, $target, $data);
    }

    public function archive(User $actor, CmsSection $target): CmsSection
    {
        return $this->archive->handle($actor, $target);
    }
}
