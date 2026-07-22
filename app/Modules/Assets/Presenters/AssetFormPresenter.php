<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;

class AssetFormPresenter
{
    public function __construct(
        private readonly AssetCreateFormPresenter $create,
        private readonly AssetEditFormPresenter $edit,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Asset $asset = null, array $defaults = []): array
    {
        return $asset
            ? $this->edit->present($actor, $asset)
            : $this->create->present($actor, $defaults);
    }
}
