<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Data\AssetFormData;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Assets\Support\AssetOptions;
use App\Modules\Shared\ResourcePresenter;

class AssetFormDefinitionPresenter
{
    public function __construct(
        private readonly AssetFormOptionPresenter $options,
        private readonly AssetMetadata $metadata,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function fields(User $actor, AssetFormData $data, ?Asset $asset = null): array
    {
        $fields = [];

        if ($actor->hasRole('superadmin') && ! $asset) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.assets.portfolio'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.assets.portfolio_help'),
                'reloadOnChange' => ['queryKey' => 'portfolio_id'],
                'options' => $this->options->portfolios($data->portfolios),
            ];
        }

        $statuses = $asset?->status === 'archived'
            ? ['archived']
            : AssetOptions::MUTABLE_STATUSES;
        $fields = [
            ...$fields,
            [
                'name' => 'parent_id',
                'label' => trans('app.assets.parent_asset'),
                'type' => 'select',
                'options' => [
                    $this->options->option('', trans('app.assets.no_parent')),
                    ...$this->options->assets($data->parents),
                ],
            ],
            ['name' => 'asset_type', 'label' => trans('app.assets.asset_type'), 'type' => 'select', 'required' => true, 'options' => $this->options->values(AssetOptions::TYPES, 'assets.types')],
            ['name' => 'usage_type', 'label' => trans('app.assets.usage_type'), 'type' => 'select', 'required' => true, 'options' => $this->options->values(AssetOptions::USAGES, 'assets.usages')],
            ['name' => 'title_en', 'label' => trans('app.assets.title_en'), 'required' => true],
            ['name' => 'title_ar', 'label' => trans('app.assets.title_ar'), 'required' => true],
        ];

        if (! $asset) {
            $fields[] = ['name' => 'code', 'label' => trans('app.assets.code'), 'help' => trans('app.assets.code_help')];
        }

        $fields = [
            ...$fields,
            ['name' => 'status', 'label' => trans('app.assets.status'), 'type' => 'select', 'required' => true, 'options' => $this->options->values($statuses, 'status')],
            ['name' => 'occupancy_status', 'label' => trans('app.assets.occupancy'), 'type' => 'select', 'required' => true, 'options' => $this->options->values(AssetOptions::OCCUPANCIES, 'status')],
            ['name' => 'valuation_amount', 'label' => trans('app.assets.valuation'), 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'currency', 'label' => trans('app.assets.currency'), 'placeholder' => 'SAR'],
            ['name' => 'area', 'label' => trans('app.assets.area'), 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'level_label', 'label' => trans('app.assets.level_label')],
            ['name' => 'unit_label', 'label' => trans('app.assets.unit_label')],
            ['name' => 'map_zone_en', 'label' => trans('app.fields.zone_en'), 'placeholder' => trans('app.assets.zone_en_placeholder')],
            ['name' => 'map_zone_ar', 'label' => trans('app.fields.zone_ar'), 'placeholder' => trans('app.assets.zone_ar_placeholder')],
            ['name' => 'land_number', 'label' => trans('app.assets.land_number'), 'placeholder' => trans('app.assets.land_number_placeholder'), 'help' => trans('app.assets.land_number_help')],
            ['name' => 'latitude', 'label' => trans('app.assets.latitude'), 'type' => 'number', 'step' => '0.000001', 'min' => -90, 'max' => 90],
            ['name' => 'longitude', 'label' => trans('app.assets.longitude'), 'type' => 'number', 'step' => '0.000001', 'min' => -180, 'max' => 180],
            [
                'name' => 'primary_owner_user_id',
                'label' => trans('app.assets.primary_owner'),
                'type' => 'select',
                'options' => [$this->options->option('', trans('app.assets.unassigned')), ...$this->options->users($data->owners)],
            ],
            [
                'name' => 'primary_manager_user_id',
                'label' => trans('app.assets.primary_manager'),
                'type' => 'select',
                'options' => [$this->options->option('', trans('app.assets.unassigned')), ...$this->options->users($data->managers)],
            ],
            ['name' => 'address', 'label' => trans('app.fields.address_en'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'address_ar', 'label' => trans('app.fields.address_ar'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'description_en', 'label' => trans('app.assets.description_en'), 'type' => 'textarea'],
            ['name' => 'description_ar', 'label' => trans('app.assets.description_ar'), 'type' => 'textarea'],
            ['name' => 'rentable', 'label' => trans('app.assets.rentable'), 'type' => 'checkbox', 'help' => trans('app.assets.rentable_help')],
        ];

        return $this->resources->sectionFields($fields, [
            trans('app.assets.structure_section') => [
                'description' => trans('app.assets.structure_section_help'),
                'fields' => ['portfolio_id', 'parent_id', 'asset_type', 'usage_type', 'title_en', 'title_ar', 'code', 'status', 'occupancy_status'],
            ],
            trans('app.assets.valuation_section') => [
                'description' => trans('app.assets.valuation_section_help'),
                'fields' => ['valuation_amount', 'currency', 'area', 'level_label', 'unit_label'],
            ],
            trans('app.assets.map_section') => [
                'description' => trans('app.assets.map_section_help'),
                'fields' => ['map_zone_en', 'map_zone_ar', 'land_number', 'latitude', 'longitude', 'address', 'address_ar'],
            ],
            trans('app.assets.assignment_section') => [
                'description' => trans('app.assets.assignment_section_help'),
                'fields' => ['primary_owner_user_id', 'primary_manager_user_id'],
            ],
            trans('app.assets.leasing_section') => [
                'description' => trans('app.assets.leasing_section_help'),
                'fields' => ['description_en', 'description_ar', 'rentable'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function values(AssetFormData $data, ?Asset $asset = null, array $defaults = []): array
    {
        $attributes = $asset ? $asset->getAttributes() : [];

        return [
            'portfolio_id' => (string) $data->portfolioId,
            'parent_id' => (string) ($attributes['parent_id'] ?? $defaults['parent_id'] ?? ''),
            'asset_type' => $attributes['asset_type'] ?? 'building',
            'usage_type' => $attributes['usage_type'] ?? 'residential',
            'title_en' => $attributes['title_en'] ?? '',
            'title_ar' => $attributes['title_ar'] ?? '',
            'code' => $attributes['code'] ?? '',
            'status' => $attributes['status'] ?? 'active',
            'occupancy_status' => $attributes['occupancy_status'] ?? 'vacant',
            'valuation_amount' => (float) ($attributes['valuation_amount'] ?? 0),
            'currency' => $attributes['currency'] ?? 'SAR',
            'area' => (float) ($attributes['area'] ?? 0),
            'level_label' => $attributes['level_label'] ?? '',
            'unit_label' => $attributes['unit_label'] ?? '',
            'map_zone_en' => $this->metadata->get($asset, 'zone_en') ?? $this->metadata->get($asset, 'zone') ?? '',
            'map_zone_ar' => $this->metadata->get($asset, 'zone_ar') ?? '',
            'land_number' => $this->metadata->get($asset, 'land_number') ?? '',
            'latitude' => $this->metadata->get($asset, 'latitude') ?? '',
            'longitude' => $this->metadata->get($asset, 'longitude') ?? '',
            'primary_owner_user_id' => (string) ($data->ownerId ?? ''),
            'primary_manager_user_id' => (string) ($data->managerId ?? ''),
            'address' => $attributes['address'] ?? '',
            'address_ar' => $attributes['address_ar'] ?? '',
            'description_en' => $attributes['description_en'] ?? '',
            'description_ar' => $attributes['description_ar'] ?? '',
            'rentable' => (bool) ($attributes['rentable'] ?? false),
        ];
    }
}
