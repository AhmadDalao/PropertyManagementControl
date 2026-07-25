<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final class LeaseWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $hasSignedPdf = $lease->documents->contains('type', 'signed_contract');
        $usesMoveInProgress = $data->adminMode
            && in_array($lease->status, ['draft', 'active'], true);

        return [
            'eyebrow' => trans($usesMoveInProgress
                ? 'app.leases.contract_controls_eyebrow'
                : 'app.resource.next_step'),
            'title' => trans($usesMoveInProgress
                ? 'app.leases.contract_controls_title'
                : "app.leases.workflow_{$lease->status}_title"),
            'description' => trans($usesMoveInProgress
                ? 'app.leases.contract_controls_description'
                : ($lease->status === 'active' && ! $hasSignedPdf
                    ? 'app.leases.workflow_active_unsigned_description'
                    : "app.leases.workflow_{$lease->status}_description")),
            'status' => trans("app.status.{$lease->status}"),
            'tone' => $usesMoveInProgress
                ? 'muted'
                : $this->tone($lease->status, $hasSignedPdf),
            'icon' => $usesMoveInProgress ? 'bi-signpost-split' : 'bi-file-earmark-check',
            'actions' => $data->adminMode
                ? $this->adminActions($data)
                : $this->tenantActions($data),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function adminActions(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $actions = [];

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
            'external' => true,
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
            'external' => true,
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
