<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceVendorDetailData;
use App\Modules\Shared\ResourcePresenter;

final class MaintenanceVendorOverviewPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(MaintenanceVendorDetailData $data): array
    {
        return [
            ['label' => trans('app.maintenance_vendors.status'), 'value' => $data->statusLabel, 'tone' => $data->statusTone],
            ['label' => trans('app.maintenance_vendors.open_work_orders'), 'value' => $data->counts['open'], 'tone' => $data->counts['open'] > 0 ? 'primary' : 'muted'],
            ['label' => trans('app.maintenance_vendors.overdue_visits'), 'value' => $data->counts['overdue'], 'tone' => $data->counts['overdue'] > 0 ? 'danger' : 'teal'],
            ['label' => trans('app.maintenance_vendors.completed_work_orders'), 'value' => $data->counts['completed'], 'tone' => 'teal'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(MaintenanceVendorDetailData $data): array
    {
        $vendor = $data->vendor;

        return [[
            'key' => 'identity',
            'title' => trans('app.maintenance_vendors.identity_section'),
            'description' => trans('app.maintenance_vendors.identity_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.maintenance_vendors.name'), 'value' => $vendor->name],
                ['label' => trans('app.maintenance_vendors.category'), 'value' => $data->categoryLabel],
                ['label' => trans('app.maintenance_vendors.status'), 'value' => $data->statusLabel],
                ['label' => trans('app.maintenance_vendors.portfolio'), 'value' => $this->resources->localized($vendor->portfolio?->name_en, $vendor->portfolio?->name_ar)],
                ['label' => trans('app.maintenance_vendors.notes'), 'value' => $vendor->notes ?: trans('app.maintenance_vendors.not_recorded')],
            ]),
        ], [
            'key' => 'contact',
            'title' => trans('app.maintenance_vendors.contact_section'),
            'description' => trans('app.maintenance_vendors.contact_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.maintenance_vendors.contact_name'), 'value' => $vendor->contact_name ?: trans('app.maintenance_vendors.not_recorded')],
                ['label' => trans('app.maintenance_vendors.phone'), 'value' => $vendor->phone ?: trans('app.maintenance_vendors.not_recorded')],
                ['label' => trans('app.maintenance_vendors.email'), 'value' => $vendor->email ?: trans('app.maintenance_vendors.not_recorded')],
            ]),
        ], [
            'key' => 'schedule',
            'title' => trans('app.maintenance_vendors.schedule_section'),
            'description' => trans('app.maintenance_vendors.schedule_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.maintenance_vendors.active_work_orders'), 'value' => $data->counts['active']],
                ['label' => trans('app.maintenance_vendors.overdue_visits'), 'value' => $data->counts['overdue'], 'tone' => $data->counts['overdue'] > 0 ? 'danger' : 'teal'],
                ['label' => trans('app.maintenance_vendors.due_today'), 'value' => $data->counts['today']],
                ['label' => trans('app.maintenance_vendors.upcoming_visits'), 'value' => $data->counts['upcoming']],
                ['label' => trans('app.maintenance_vendors.unscheduled_work'), 'value' => $data->counts['unscheduled']],
                ['label' => trans('app.maintenance_vendors.properties_served'), 'value' => $data->counts['properties']],
            ]),
        ], [
            'key' => 'financial',
            'title' => trans('app.maintenance_vendors.financial_section'),
            'description' => trans('app.maintenance_vendors.financial_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.maintenance_vendors.active_quotes'), 'value' => $this->money($data->financial['active_quoted'], $data->financial['currency'])],
                ['label' => trans('app.maintenance_vendors.completed_quotes'), 'value' => $this->money($data->financial['completed_quoted'], $data->financial['currency'])],
                ['label' => trans('app.maintenance_vendors.final_costs'), 'value' => $this->money($data->financial['completed_final'], $data->financial['currency'])],
                ['label' => trans('app.maintenance_vendors.completed_variance'), 'value' => $this->variance($data), 'tone' => $this->varianceTone($data)],
            ]),
        ]];
    }

    private function variance(MaintenanceVendorDetailData $data): string
    {
        $variance = $data->financial['completed_final'] - $data->financial['completed_quoted'];
        $prefix = $variance > 0 ? '+' : '';

        return $prefix.$this->money($variance, $data->financial['currency']);
    }

    private function varianceTone(MaintenanceVendorDetailData $data): string
    {
        $variance = $data->financial['completed_final'] - $data->financial['completed_quoted'];

        return $variance > 0 ? 'danger' : ($variance < 0 ? 'teal' : 'muted');
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
