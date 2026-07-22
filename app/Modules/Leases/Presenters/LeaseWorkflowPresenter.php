<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\Leases\Data\LeaseDetailData;
use App\Support\PortfolioModules;

final class LeaseWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $hasSignedPdf = $lease->documents->contains('type', 'signed_contract');

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.leases.workflow_{$lease->status}_title"),
            'description' => trans($lease->status === 'active' && ! $hasSignedPdf
                ? 'app.leases.workflow_active_unsigned_description'
                : "app.leases.workflow_{$lease->status}_description"),
            'status' => trans("app.status.{$lease->status}"),
            'tone' => $this->tone($lease->status, $hasSignedPdf),
            'icon' => 'bi-file-earmark-check',
            'actions' => $data->adminMode
                ? $this->adminActions($data, $hasSignedPdf)
                : $this->tenantActions($data),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function adminActions(LeaseDetailData $data, bool $hasSignedPdf): array
    {
        $lease = $data->lease;
        $actions = [];

        if ($lease->status === 'draft') {
            $actions[] = [
                'label' => trans('app.leases.review_activate'),
                'href' => route('leases.edit', $lease),
                'variant' => 'primary',
            ];
        }

        if ($lease->status === 'active' && PortfolioModules::enabledForUser($data->actor, 'payments')) {
            $actions[] = [
                'label' => trans('app.leases.record_payment'),
                'href' => route('payments.create', ['lease_id' => $lease->id]),
                'variant' => 'primary',
            ];
        }

        if (
            ! $hasSignedPdf
            && in_array($lease->status, ['draft', 'active'], true)
            && PortfolioModules::enabledForUser($data->actor, 'documents')
        ) {
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

        if (
            in_array($lease->status, ['expired', 'terminated'], true)
            && (float) $lease->balance_remaining > 0
            && PortfolioModules::enabledForUser($data->actor, 'payments')
        ) {
            $actions[] = [
                'label' => trans('app.leases.record_final_payment'),
                'href' => route('payments.create', ['lease_id' => $lease->id]),
                'variant' => 'primary',
            ];
        }

        if (in_array($lease->status, ['active', 'expired'], true)) {
            $actions[] = $lease->renewalLease
                ? [
                    'label' => trans('app.leases.open_renewal'),
                    'href' => route('leases.show', $lease->renewalLease),
                    'variant' => 'secondary',
                ]
                : [
                    'label' => trans('app.leases.prepare_renewal'),
                    'href' => route('leases.renew', $lease),
                    'variant' => 'secondary',
                ];
        }

        $actions[] = [
            'label' => trans('app.leases.tenant_statement'),
            'href' => route('leases.statement', $lease),
            'variant' => 'secondary',
        ];

        if (in_array($lease->status, ['draft', 'active'], true)) {
            $actions[] = [
                'label' => trans('app.leases.terminate'),
                'href' => route('leases.destroy', $lease),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.leases.terminate_confirm', ['code' => $lease->code]),
            ];
        }

        return $actions;
    }

    /** @return array<int, array<string, mixed>> */
    private function tenantActions(LeaseDetailData $data): array
    {
        return [[
            'label' => trans('app.leases.tenant_statement'),
            'href' => route('leases.statement', $data->lease),
            'variant' => 'primary',
        ]];
    }

    private function tone(string $status, bool $hasSignedPdf): string
    {
        return match (true) {
            $status === 'active' && $hasSignedPdf => 'teal',
            $status === 'active', $status === 'draft' => 'primary',
            $status === 'terminated' => 'danger',
            default => 'muted',
        };
    }
}
