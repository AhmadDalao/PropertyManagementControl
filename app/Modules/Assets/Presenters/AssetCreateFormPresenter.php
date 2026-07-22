<?php

namespace App\Modules\Assets\Presenters;

use App\Models\User;
use App\Modules\Assets\Queries\AssetFormOptionsQuery;

class AssetCreateFormPresenter
{
    public function __construct(
        private readonly AssetFormOptionsQuery $options,
        private readonly AssetFormDefinitionPresenter $definition,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, array $defaults): array
    {
        $data = $this->options->get($actor, defaults: $defaults);

        return [
            'title' => trans('app.assets.create_asset'),
            'description' => trans('app.assets.create_description'),
            'backHref' => route('assets.index'),
            'backLabel' => trans('app.assets.all_assets'),
            'action' => route('assets.store'),
            'method' => 'post',
            'submitLabel' => trans('app.assets.create_asset'),
            'fields' => $this->definition->fields($actor, $data),
            'initialValues' => $this->definition->values($data, defaults: $defaults),
        ];
    }
}
