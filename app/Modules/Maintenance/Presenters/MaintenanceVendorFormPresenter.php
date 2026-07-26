<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceVendor;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Maintenance\Support\MaintenanceVendorAccess;
use App\Modules\Maintenance\Support\MaintenanceVendorOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

final class MaintenanceVendorFormPresenter
{
    public function __construct(
        private readonly MaintenanceVendorAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?MaintenanceVendor $vendor = null, ?int $portfolioId = null): array
    {
        $this->access->ensureManager($actor);

        if ($vendor) {
            $this->access->ensureCanAccess($actor, $vendor);
        }

        $creating = $vendor === null;
        $initial = $vendor ? [
            'portfolio_id' => (string) $vendor->portfolio_id,
            'name' => $vendor->name,
            'service_category' => $vendor->service_category,
            'contact_name' => $vendor->contact_name ?? '',
            'phone' => $vendor->phone ?? '',
            'email' => $vendor->email ?? '',
            'status' => $vendor->status,
            'notes' => $vendor->notes ?? '',
        ] : [
            'portfolio_id' => (string) ($portfolioId ?: $actor->portfolio_id ?: ''),
            'name' => '',
            'service_category' => 'general',
            'contact_name' => '',
            'phone' => '',
            'email' => '',
            'status' => 'active',
            'notes' => '',
        ];
        $fields = [];

        if ($creating && $actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.maintenance_vendors.portfolio'),
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => '', 'label' => trans('app.maintenance_vendors.choose_portfolio')],
                    ...collect($this->portfolios->options($actor, true))->map(fn (array $option): array => [
                        'value' => $option['id'],
                        'label' => $option['name'],
                    ])->all(),
                ],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => trans('app.maintenance_vendors.name'), 'required' => true, 'max' => 255],
            ['name' => 'service_category', 'label' => trans('app.maintenance_vendors.category'), 'type' => 'select', 'required' => true, 'options' => $this->options(MaintenanceOptions::CATEGORIES)],
            ['name' => 'contact_name', 'label' => trans('app.maintenance_vendors.contact_name'), 'max' => 255],
            ['name' => 'phone', 'label' => trans('app.maintenance_vendors.phone'), 'max' => 40],
            ['name' => 'email', 'label' => trans('app.maintenance_vendors.email'), 'type' => 'email', 'max' => 255],
            ['name' => 'status', 'label' => trans('app.maintenance_vendors.status'), 'type' => 'select', 'required' => true, 'options' => $this->options(MaintenanceVendorOptions::STATUSES)],
            ['name' => 'notes', 'label' => trans('app.maintenance_vendors.notes'), 'type' => 'textarea', 'rows' => 4],
        ];

        return [
            'title' => trans($creating
                ? 'app.maintenance_vendors.create'
                : 'app.maintenance_vendors.edit'),
            'description' => trans($creating
                ? 'app.maintenance_vendors.create_description'
                : 'app.maintenance_vendors.edit_description'),
            'backHref' => $vendor
                ? route('maintenance-vendors.show', $vendor)
                : route('maintenance-vendors.index'),
            'backLabel' => trans('app.maintenance_vendors.directory'),
            'action' => $vendor
                ? route('maintenance-vendors.update', $vendor)
                : route('maintenance-vendors.store'),
            'method' => $vendor ? 'put' : 'post',
            'submitLabel' => trans($vendor
                ? 'app.maintenance_vendors.update'
                : 'app.maintenance_vendors.create'),
            'fields' => $this->resources->sectionFields($fields, [
                trans('app.maintenance_vendors.identity_section') => [
                    'description' => trans('app.maintenance_vendors.identity_section_help'),
                    'fields' => ['portfolio_id', 'name', 'service_category', 'status'],
                ],
                trans('app.maintenance_vendors.contact_section') => [
                    'description' => trans('app.maintenance_vendors.contact_section_help'),
                    'fields' => ['contact_name', 'phone', 'email'],
                ],
                trans('app.maintenance_vendors.notes_section') => [
                    'description' => trans('app.maintenance_vendors.notes_section_help'),
                    'fields' => ['notes'],
                ],
            ]),
            'initialValues' => $initial,
        ];
    }

    /**
     * @param  array<int, string>  $values
     * @return list<array{value:string,label:string}>
     */
    private function options(array $values): array
    {
        $options = [];

        foreach ($values as $value) {
            $label = trans("app.status.{$value}");
            $options[] = [
                'value' => $value,
                'label' => is_string($label) ? $label : $value,
            ];
        }

        return $options;
    }
}
