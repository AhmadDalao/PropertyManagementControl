<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Data\TenantDetailData;
use Illuminate\Support\Collection;

final class TenantRelatedPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function present(TenantDetailData $data): array
    {
        return [
            $this->leases($data->leases, $data->tenant->id),
            $this->payments($data->payments),
            $this->maintenance($data->maintenance),
        ];
    }

    /**
     * @param  Collection<int, Lease>  $leases
     * @return array<string, mixed>
     */
    private function leases(Collection $leases, int $tenantId): array
    {
        return [
            'title' => trans('app.tenants.leases'),
            'description' => trans('app.tenants.leases_help'),
            'columns' => [
                trans('app.tenants.lease'),
                trans('app.tenants.asset'),
                trans('app.tenants.status'),
                trans('app.tenants.balance'),
            ],
            'rows' => $leases->map(fn (Lease $lease): array => [
                trans('app.tenants.lease') => [
                    'label' => $lease->code,
                    'href' => route('leases.show', $lease),
                ],
                trans('app.tenants.asset') => $lease->leaseable
                    ? [
                        'label' => $this->resources->localized(
                            $lease->leaseable->getAttribute('title_en'),
                            $lease->leaseable->getAttribute('title_ar'),
                        ) ?? '-',
                        'href' => route('assets.show', $lease->leaseable),
                    ]
                    : '-',
                trans('app.tenants.status') => trans("app.status.{$lease->status}"),
                trans('app.tenants.balance') => number_format((float) $lease->balance_remaining, 2).' '.$lease->currency,
            ])->all(),
            'emptyText' => trans('app.tenants.no_leases'),
            'actionHref' => route('leases.create', ['tenant_profile_id' => $tenantId]),
            'actionLabel' => trans('app.tenants.create_lease'),
        ];
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<string, mixed>
     */
    private function payments(Collection $payments): array
    {
        return [
            'title' => trans('app.tenants.payments'),
            'description' => trans('app.tenants.payments_help'),
            'columns' => [
                trans('app.tenants.reference'),
                trans('app.tenants.received_on'),
                trans('app.tenants.amount'),
                trans('app.tenants.status'),
            ],
            'rows' => $payments->map(fn (Payment $payment): array => [
                trans('app.tenants.reference') => [
                    'label' => $payment->reference ?: trans('app.payments.payment_number', ['id' => $payment->id]),
                    'href' => route('payments.show', $payment),
                ],
                trans('app.tenants.received_on') => $payment->received_on?->toDateString() ?? '-',
                trans('app.tenants.amount') => number_format((float) $payment->amount, 2).' '.$payment->currency,
                trans('app.tenants.status') => trans("app.status.{$payment->status}"),
            ])->all(),
            'emptyText' => trans('app.tenants.no_payments'),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceRequest>  $requests
     * @return array<string, mixed>
     */
    private function maintenance(Collection $requests): array
    {
        return [
            'title' => trans('app.tenants.maintenance'),
            'description' => trans('app.tenants.maintenance_help'),
            'columns' => [
                trans('app.tenants.request'),
                trans('app.tenants.asset'),
                trans('app.tenants.status'),
                trans('app.tenants.priority'),
            ],
            'rows' => $requests->map(fn (MaintenanceRequest $request): array => [
                trans('app.tenants.request') => [
                    'label' => '#'.$request->id.' '.$request->title,
                    'href' => route('maintenance-requests.show', $request),
                ],
                trans('app.tenants.asset') => $request->asset
                    ? [
                        'label' => $this->resources->localized(
                            $request->asset->title_en,
                            $request->asset->title_ar,
                        ) ?? '-',
                        'href' => route('assets.show', $request->asset),
                    ]
                    : '-',
                trans('app.tenants.status') => trans("app.status.{$request->status}"),
                trans('app.tenants.priority') => trans("app.status.{$request->priority}"),
            ])->all(),
            'emptyText' => trans('app.tenants.no_maintenance'),
        ];
    }
}
