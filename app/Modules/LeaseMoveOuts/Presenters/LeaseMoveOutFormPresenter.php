<?php

namespace App\Modules\LeaseMoveOuts\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutGuard;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutOptions;
use App\Modules\Leases\Support\LeaseAccess;

final readonly class LeaseMoveOutFormPresenter
{
    public function __construct(
        private LeaseAccess $access,
        private LeaseMoveOutGuard $guard,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, Lease $lease): array
    {
        $this->access->ensureCanManage($actor, $lease);
        $this->guard->ensurePlannable($lease);
        $lease->loadMissing('moveOut', 'tenantProfile.user', 'leaseable', 'installments');
        $moveOut = $lease->moveOut;
        abort_if($moveOut?->status === 'completed', 409, trans('app.errors.move_out_completed_locked'));
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $tenant = $lease->tenantProfile?->user->name ?? trans('app.lease_move_outs.no_tenant');
        $assetName = $asset
            ? (app()->isLocale('ar') ? ($asset->title_ar ?: $asset->title_en) : ($asset->title_en ?: $asset->title_ar))
            : trans('app.lease_move_outs.no_asset');
        $initialValues = $moveOut
            ? [
                'move_out_date' => $moveOut->move_out_date?->toDateString()
                    ?? $this->defaultDate($lease),
                'reason' => $moveOut->reason,
                'deposit_disposition' => $moveOut->deposit_disposition,
                'deposit_deduction_amount' => (float) $moveOut->deposit_deduction_amount,
                'keys_returned' => (bool) $moveOut->keys_returned,
                'notes' => $moveOut->notes ?? '',
            ]
            : [
                'move_out_date' => $this->defaultDate($lease),
                'reason' => $lease->status === 'expired' ? 'natural_expiry' : 'tenant_notice',
                'deposit_disposition' => (float) $lease->deposit_amount > 0 ? 'pending' : 'not_applicable',
                'deposit_deduction_amount' => 0,
                'keys_returned' => false,
                'notes' => '',
            ];

        return [
            'title' => trans($moveOut ? 'app.lease_move_outs.edit_title' : 'app.lease_move_outs.create_title', [
                'code' => $lease->code,
            ]),
            'description' => trans('app.lease_move_outs.form_description', [
                'tenant' => $tenant,
                'asset' => $assetName,
            ]),
            'backHref' => route('leases.show', $lease),
            'backLabel' => trans('app.lease_move_outs.back_to_lease'),
            'action' => route('leases.move-out.update', $lease),
            'method' => 'put',
            'submitLabel' => trans($moveOut
                ? 'app.lease_move_outs.update_plan'
                : 'app.lease_move_outs.save_plan'),
            'fields' => $this->fields($lease),
            'initialValues' => $initialValues,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fields(Lease $lease): array
    {
        $schedule = trans('app.lease_move_outs.schedule_section');
        $handover = trans('app.lease_move_outs.handover_section');

        return [
            [
                'name' => 'move_out_date',
                'label' => trans('app.lease_move_outs.move_out_date'),
                'type' => 'date',
                'required' => true,
                'section' => $schedule,
                'sectionDescription' => trans('app.lease_move_outs.schedule_help'),
            ],
            [
                'name' => 'reason',
                'label' => trans('app.lease_move_outs.reason'),
                'type' => 'select',
                'required' => true,
                'options' => $this->options(LeaseMoveOutOptions::REASONS, 'reason'),
                'section' => $schedule,
            ],
            [
                'name' => 'deposit_disposition',
                'label' => trans('app.lease_move_outs.deposit_disposition'),
                'type' => 'select',
                'required' => true,
                'options' => $this->options(LeaseMoveOutOptions::DEPOSIT_DISPOSITIONS, 'deposit'),
                'help' => trans('app.lease_move_outs.deposit_help', [
                    'amount' => number_format((float) $lease->deposit_amount, 2),
                    'currency' => $lease->currency,
                ]),
                'section' => $handover,
                'sectionDescription' => trans('app.lease_move_outs.handover_help'),
            ],
            [
                'name' => 'deposit_deduction_amount',
                'label' => trans('app.lease_move_outs.deposit_deduction'),
                'type' => 'number',
                'step' => '0.01',
                'min' => 0,
                'max' => (float) $lease->deposit_amount,
                'help' => trans('app.lease_move_outs.deposit_deduction_help'),
                'section' => $handover,
            ],
            [
                'name' => 'keys_returned',
                'label' => trans('app.lease_move_outs.keys_returned'),
                'type' => 'checkbox',
                'help' => trans('app.lease_move_outs.keys_returned_help'),
                'section' => $handover,
            ],
            [
                'name' => 'notes',
                'label' => trans('app.lease_move_outs.notes'),
                'type' => 'textarea',
                'rows' => 5,
                'help' => trans('app.lease_move_outs.notes_help'),
                'section' => $handover,
            ],
        ];
    }

    /** @param list<string> $values
     * @return list<array{label:string,value:string}>
     */
    private function options(array $values, string $group): array
    {
        return array_values(
            collect($values)
                ->map(fn (string $value): array => [
                    'label' => (string) trans("app.lease_move_outs.{$group}_{$value}"),
                    'value' => $value,
                ])
                ->all(),
        );
    }

    private function defaultDate(Lease $lease): string
    {
        return $lease->ends_at?->greaterThanOrEqualTo(today())
            ? $lease->ends_at->toDateString()
            : today()->toDateString();
    }
}
