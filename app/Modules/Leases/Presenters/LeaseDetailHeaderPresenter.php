<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Asset;
use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Shared\ResourcePresenter;

final class LeaseDetailHeaderPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $assetTitle = $this->resources->localized($asset?->title_en, $asset?->title_ar)
            ?? trans('app.leases.no_asset');
        $tenant = $lease->tenantProfile?->user->name ?? trans('app.leases.no_tenant');
        $actions = [];

        if ($data->adminMode) {
            $actions[] = ['label' => trans('app.leases.edit_action'), 'href' => route('leases.edit', $lease), 'variant' => 'primary'];
        }

        $actions[] = ['label' => trans('app.leases.contract_pdf'), 'href' => route('leases.contract', $lease), 'variant' => 'secondary'];

        if ($data->adminMode) {
            $actions[] = [
                'label' => trans('app.leases.upload_signed_pdf'),
                'href' => route('documents.create', [
                    'documentable_type' => 'lease',
                    'documentable_id' => $lease->id,
                    'type' => 'signed_contract',
                    'title_en' => "Signed contract {$lease->code}",
                    'title_ar' => "العقد الموقع {$lease->code}",
                ]),
                'variant' => 'secondary',
            ];
        }

        $actions[] = ['label' => trans('app.leases.tenant_statement'), 'href' => route('leases.statement', $lease), 'variant' => 'secondary'];

        if ($data->adminMode) {
            $actions[] = ['label' => trans('app.leases.record_payment'), 'href' => route('payments.create', ['lease_id' => $lease->id]), 'variant' => 'secondary'];
        }

        return [
            'eyebrow' => trans('app.leases.detail_eyebrow'),
            'title' => $lease->code,
            'description' => trans('app.leases.detail_description', [
                'tenant' => $tenant,
                'asset' => $assetTitle,
                'status' => trans("app.status.{$lease->status}"),
            ]),
            'backHref' => $data->adminMode ? route('leases.index') : route('dashboard'),
            'backLabel' => $data->adminMode ? trans('app.leases.all_leases') : trans('app.nav.dashboard'),
            'actions' => $actions,
        ];
    }
}
