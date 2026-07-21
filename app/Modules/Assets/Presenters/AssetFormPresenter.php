<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

class AssetFormPresenter
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
        private readonly AssetMetadata $metadata,
    ) {}

    /**
     * @param  array{portfolio_id?:mixed,parent_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Asset $asset = null, array $defaults = []): array
    {
        $asset?->loadMissing('stakeholders.user');
        $portfolioOptions = $this->portfolios->options($actor);
        $portfolioId = (int) ($asset->portfolio_id
            ?? $defaults['portfolio_id']
            ?? $actor->portfolio_id
            ?? ($portfolioOptions[0]['id'] ?? 0));
        $owner = $asset?->stakeholders?->firstWhere('relationship_type', 'owner');
        $manager = $asset?->stakeholders?->firstWhere('relationship_type', 'manager');
        $parentOptions = $this->portfolios->apply(Asset::query()->orderBy('title_en'), $actor)
            ->when($asset, fn ($query) => $query->whereKeyNot($asset->id))
            ->get()
            ->map(fn (Asset $record) => $this->option(
                $record->id,
                $this->resources->localized($record->title_en, $record->title_ar)
                    .' · '.$record->code.' · '.$record->asset_type,
            ))
            ->prepend(['value' => '', 'label' => 'No parent'])
            ->values()
            ->all();
        $userOptions = $this->portfolios->apply(
            User::query()->whereDoesntHave('roles', fn ($query) => $query->where('name', 'tenant'))->orderBy('name'),
            $actor
        )->get()
            ->map(fn (User $user) => $this->option($user->id, $user->name))
            ->prepend(['value' => '', 'label' => 'Unassigned'])
            ->values()
            ->all();
        $fields = [];

        if ($actor->hasRole('superadmin') && $asset === null) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'required' => true,
                'options' => collect($portfolioOptions)
                    ->map(fn (array $portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])
                    ->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'parent_id', 'label' => 'Parent asset', 'type' => 'select', 'options' => $parentOptions],
            ['name' => 'asset_type', 'label' => 'Asset type', 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(['property', 'building', 'floor', 'unit', 'space'])],
            ['name' => 'usage_type', 'label' => 'Usage type', 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(['residential', 'commercial', 'mixed', 'personal'])],
            ['name' => 'title_en', 'label' => 'English title', 'required' => true],
            ['name' => 'title_ar', 'label' => 'Arabic title', 'required' => true],
        ];

        if ($asset === null) {
            $fields[] = ['name' => 'code', 'label' => 'Code', 'help' => 'Leave blank to generate one.'];
        }

        $fields = [
            ...$fields,
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(['active', 'inactive', 'archived'])],
            ['name' => 'occupancy_status', 'label' => 'Occupancy', 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(['vacant', 'occupied', 'reserved', 'maintenance'])],
            ['name' => 'valuation_amount', 'label' => 'Valuation', 'type' => 'number', 'min' => 0],
            ['name' => 'currency', 'label' => 'Currency', 'placeholder' => 'SAR'],
            ['name' => 'area', 'label' => 'Area', 'type' => 'number', 'min' => 0],
            ['name' => 'level_label', 'label' => 'Floor / level label'],
            ['name' => 'unit_label', 'label' => 'Unit / space label'],
            ['name' => 'map_zone_en', 'label' => trans('app.fields.zone_en'), 'placeholder' => 'North Riyadh'],
            ['name' => 'map_zone_ar', 'label' => trans('app.fields.zone_ar'), 'placeholder' => 'شمال الرياض'],
            ['name' => 'land_number', 'label' => 'Land number', 'placeholder' => 'Land 42', 'help' => 'Click target label for the property map.'],
            ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'step' => '0.000001', 'min' => -90, 'max' => 90],
            ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'step' => '0.000001', 'min' => -180, 'max' => 180],
            ['name' => 'primary_owner_user_id', 'label' => 'Primary owner', 'type' => 'select', 'options' => $userOptions],
            ['name' => 'primary_manager_user_id', 'label' => 'Primary manager', 'type' => 'select', 'options' => $userOptions],
            ['name' => 'address', 'label' => trans('app.fields.address_en'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'address_ar', 'label' => trans('app.fields.address_ar'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'description_en', 'label' => 'English description', 'type' => 'textarea'],
            ['name' => 'description_ar', 'label' => 'Arabic description', 'type' => 'textarea'],
            ['name' => 'rentable', 'label' => 'Rentable', 'type' => 'checkbox', 'help' => 'Only rentable assets can be leased.'],
        ];
        $fields = $this->resources->sectionFields($fields, [
            'Property structure' => [
                'description' => 'Choose where the record sits in the property tree and give it clear bilingual identity.',
                'fields' => ['portfolio_id', 'parent_id', 'asset_type', 'usage_type', 'title_en', 'title_ar', 'code', 'status', 'occupancy_status'],
            ],
            'Space and valuation' => [
                'description' => 'Record the physical label, size, and current operational valuation.',
                'fields' => ['valuation_amount', 'currency', 'area', 'level_label', 'unit_label'],
            ],
            'Map and address' => [
                'description' => 'Add the bilingual location details used by the property map and directory.',
                'fields' => ['map_zone_en', 'map_zone_ar', 'land_number', 'latitude', 'longitude', 'address', 'address_ar'],
            ],
            'Ownership and management' => [
                'description' => 'Assign the people responsible for this asset.',
                'fields' => ['primary_owner_user_id', 'primary_manager_user_id'],
            ],
            'Descriptions and leasing' => [
                'description' => 'Finish the public descriptions and decide whether contracts can use this record.',
                'fields' => ['description_en', 'description_ar', 'rentable'],
            ],
        ]);

        return [
            'title' => $asset
                ? trans('app.actions.edit').' '.$this->resources->localized($asset->title_en, $asset->title_ar)
                : 'Create asset',
            'description' => 'Build the property tree cleanly before leases, documents, and reports depend on it.',
            'backHref' => $asset ? route('assets.show', $asset) : route('assets.index'),
            'backLabel' => $asset ? 'Asset detail' : 'All assets',
            'action' => $asset ? route('assets.update', $asset) : route('assets.store'),
            'method' => $asset ? 'put' : 'post',
            'submitLabel' => $asset ? 'Update asset' : 'Create asset',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) $portfolioId,
                'parent_id' => (string) ($asset->parent_id ?? $defaults['parent_id'] ?? ''),
                'asset_type' => $asset->asset_type ?? 'building',
                'usage_type' => $asset->usage_type ?? 'residential',
                'title_en' => $asset->title_en ?? '',
                'title_ar' => $asset->title_ar ?? '',
                'code' => $asset->code ?? '',
                'status' => $asset->status ?? 'active',
                'occupancy_status' => $asset->occupancy_status ?? 'vacant',
                'valuation_amount' => (float) ($asset->valuation_amount ?? 0),
                'currency' => $asset->currency ?? 'SAR',
                'area' => (float) ($asset->area ?? 0),
                'level_label' => $asset->level_label ?? '',
                'unit_label' => $asset->unit_label ?? '',
                'map_zone_en' => $this->metadata->get($asset, 'zone_en') ?? $this->metadata->get($asset, 'zone') ?? '',
                'map_zone_ar' => $this->metadata->get($asset, 'zone_ar') ?? '',
                'land_number' => $this->metadata->get($asset, 'land_number') ?? '',
                'latitude' => $this->metadata->get($asset, 'latitude') ?? '',
                'longitude' => $this->metadata->get($asset, 'longitude') ?? '',
                'primary_owner_user_id' => (string) ($owner->user_id ?? ''),
                'primary_manager_user_id' => (string) ($manager->user_id ?? ''),
                'address' => $asset->address ?? '',
                'address_ar' => $asset->address_ar ?? '',
                'description_en' => $asset->description_en ?? '',
                'description_ar' => $asset->description_ar ?? '',
                'rentable' => (bool) ($asset->rentable ?? false),
            ],
        ];
    }

    /**
     * @return array{value:string,label:string}
     */
    private function option(int|string $value, string $label): array
    {
        return ['value' => (string) $value, 'label' => $label];
    }
}
