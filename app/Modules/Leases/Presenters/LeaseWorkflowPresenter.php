<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutReadiness;
use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final readonly class LeaseWorkflowPresenter
{
    public function __construct(private LeaseMoveOutReadiness $moveOutReadiness) {}

    /** @return array<string, mixed> */
    public function present(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $hasSignedPdf = $lease->documents->contains('type', 'signed_contract');
        $moveOut = $lease->moveOut;
        $usesMoveOut = $data->adminMode
            && $moveOut
            && $moveOut->status !== 'cancelled';
        $usesMoveInProgress = $data->adminMode
            && in_array($lease->status, ['draft', 'active'], true)
            && ! $usesMoveOut;

        return [
            'eyebrow' => trans($usesMoveOut
                ? 'app.lease_move_outs.controls_eyebrow'
                : ($usesMoveInProgress
                    ? 'app.leases.contract_controls_eyebrow'
                    : 'app.resource.next_step')),
            'title' => trans($usesMoveOut
                ? "app.lease_move_outs.controls_{$moveOut->status}_title"
                : ($usesMoveInProgress
                    ? 'app.leases.contract_controls_title'
                    : "app.leases.workflow_{$lease->status}_title")),
            'description' => trans($usesMoveOut
                ? "app.lease_move_outs.controls_{$moveOut->status}_description"
                : ($usesMoveInProgress
                    ? 'app.leases.contract_controls_description'
                    : ($lease->status === 'active' && ! $hasSignedPdf
                        ? 'app.leases.workflow_active_unsigned_description'
                        : "app.leases.workflow_{$lease->status}_description"))),
            'status' => trans("app.status.{$lease->status}"),
            'tone' => $usesMoveOut
                ? ($moveOut->status === 'completed' ? 'teal' : 'primary')
                : ($usesMoveInProgress
                    ? 'muted'
                    : $this->tone($lease->status, $hasSignedPdf)),
            'icon' => $usesMoveOut
                ? 'bi-box-arrow-right'
                : ($usesMoveInProgress ? 'bi-signpost-split' : 'bi-file-earmark-check'),
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
        $moveOut = $lease->moveOut;
        $usesMoveOut = $moveOut && $moveOut->status !== 'cancelled';

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

        if ($usesMoveOut && $moveOut->status === 'planned') {
            $actions[] = [
                'label' => trans('app.lease_move_outs.update_plan'),
                'href' => route('leases.move-out.edit', $lease),
                'variant' => 'primary',
            ];

            if ($this->moveOutReadiness->for($lease, $moveOut)['ready']) {
                $actions[] = [
                    'label' => trans('app.lease_move_outs.complete_move_out'),
                    'href' => route('leases.move-out.complete', $lease),
                    'method' => 'post',
                    'variant' => 'primary',
                    'confirm' => trans('app.lease_move_outs.complete_confirm', ['code' => $lease->code]),
                ];
            }

            $actions[] = [
                'label' => trans('app.lease_move_outs.cancel_plan'),
                'href' => route('leases.move-out.destroy', $lease),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.lease_move_outs.cancel_confirm', ['code' => $lease->code]),
            ];
        } elseif (! $usesMoveOut && in_array($lease->status, ['active', 'expired'], true)) {
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

        if (! $usesMoveOut && in_array($lease->status, ['active', 'expired'], true)) {
            $actions[] = [
                'label' => trans('app.lease_move_outs.plan_move_out'),
                'href' => route('leases.move-out.edit', $lease),
                'variant' => 'danger',
            ];
        }

        if ($lease->status === 'draft') {
            $actions[] = [
                'label' => trans('app.leases.cancel_draft'),
                'href' => route('leases.destroy', $lease),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.leases.cancel_draft_confirm', ['code' => $lease->code]),
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
