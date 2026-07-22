<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Queries\AssetFormOptionsQuery;

class AssetEditFormPresenter
{
    public function __construct(
        private readonly AssetFormOptionsQuery $options,
        private readonly AssetFormDefinitionPresenter $definition,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, Asset $asset): array
    {
        $data = $this->options->get($actor, $asset);

        return [
            'title' => trans('app.assets.edit_asset', [
                'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
            ]),
            'description' => trans('app.assets.edit_description'),
            'backHref' => route('assets.show', $asset),
            'backLabel' => trans('app.assets.asset_detail'),
            'action' => route('assets.update', $asset),
            'method' => 'put',
            'submitLabel' => trans('app.assets.update_asset'),
            'fields' => $this->definition->fields($actor, $data, $asset),
            'initialValues' => $this->definition->values($data, $asset),
        ];
    }
}
