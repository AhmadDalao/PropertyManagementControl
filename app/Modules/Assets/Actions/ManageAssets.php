<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\User;

class ManageAssets
{
    public function __construct(
        private readonly CreateAsset $create,
        private readonly UpdateAsset $update,
        private readonly ArchiveAsset $archive,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Asset
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, Asset $asset, array $data): Asset
    {
        return $this->update->handle($actor, $asset, $data);
    }

    public function archive(User $actor, Asset $asset): bool
    {
        return $this->archive->handle($actor, $asset);
    }
}
