<?php

namespace App\Modules\LeaseMoveOuts\Presenters;

use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutReadiness;
use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final readonly class LeaseMoveOutProgressPresenter
{
    public function __construct(private LeaseMoveOutReadiness $readiness) {}

    /** @return array<string, mixed>|null */
    public function present(LeaseDetailData $data): ?array
    {
        $lease = $data->lease;
        $moveOut = $lease->moveOut;

        if (! $data->adminMode || ! $moveOut || $moveOut->status === 'cancelled') {
            return null;
        }

        $state = $this->readiness->for($lease, $moveOut);
        $steps = [
            $this->step('plan', true, route('leases.move-out.edit', $lease), 'review_plan'),
            $this->step('notice', $state['notice'], $this->documentUrl($lease->id, 'termination_notice'), 'upload_notice'),
            $this->step('inspection', $state['inspection'], $this->documentUrl($lease->id, 'move_out_inspection'), 'upload_inspection'),
            $this->step('keys', $state['keys'], route('leases.move-out.edit', $lease), 'record_keys'),
            $this->step('deposit', $state['deposit'], route('leases.move-out.edit', $lease), 'settle_deposit'),
            $this->step('handover', $moveOut->status === 'completed', route('leases.move-out.edit', $lease), 'review_handover'),
            $this->step(
                'account',
                $state['balance'] <= 0,
                PortfolioModules::enabledForUser($data->actor, 'payments')
                    ? route('payments.create', ['lease_id' => $lease->id])
                    : null,
                'record_final_payment',
            ),
        ];
        $steps = $this->sequence($steps);
        $completed = collect($steps)->where('state', 'complete')->count();

        return [
            'eyebrow' => trans('app.lease_move_outs.progress_eyebrow'),
            'title' => trans('app.lease_move_outs.progress_title'),
            'description' => trans('app.lease_move_outs.progress_description'),
            'summary' => trans('app.lease_move_outs.progress_summary', [
                'completed' => $completed,
                'total' => count($steps),
            ]),
            'completed' => $completed,
            'total' => count($steps),
            'collapseWhenComplete' => true,
            'expandLabel' => trans('app.leases.progress_show_steps'),
            'collapseLabel' => trans('app.leases.progress_hide_steps'),
            'steps' => $steps,
        ];
    }

    /** @return array<string, mixed> */
    private function step(
        string $key,
        bool $complete,
        ?string $href,
        string $action,
        bool $download = false,
    ): array {
        return [
            'title' => trans("app.lease_move_outs.step_{$key}"),
            'description' => trans("app.lease_move_outs.step_{$key}_help"),
            'state' => $complete ? 'complete' : 'pending',
            'icon' => match ($key) {
                'plan' => 'bi-calendar2-check',
                'notice' => 'bi-file-earmark-text',
                'inspection' => 'bi-clipboard-check',
                'keys' => 'bi-key',
                'deposit' => 'bi-safe',
                'account' => 'bi-cash-stack',
                default => 'bi-box-arrow-right',
            },
            'href' => $complete ? null : $href,
            'actionLabel' => $complete ? null : trans("app.lease_move_outs.{$action}"),
            'download' => $download,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     * @return list<array<string, mixed>>
     */
    private function sequence(array $steps): array
    {
        $currentAssigned = false;

        return array_values(
            collect($steps)->map(function (array $step) use (&$currentAssigned): array {
                if ($step['state'] === 'pending' && ! $currentAssigned) {
                    $step['state'] = 'current';
                    $currentAssigned = true;
                }

                return $step;
            })->all(),
        );
    }

    private function documentUrl(int $leaseId, string $type): string
    {
        return route('documents.create', [
            'documentable_type' => 'lease',
            'documentable_id' => $leaseId,
            'type' => $type,
            'is_public' => 1,
        ]);
    }
}
