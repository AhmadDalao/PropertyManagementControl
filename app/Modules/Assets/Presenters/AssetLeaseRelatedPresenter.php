<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Assets\Support\AssetLeaseBalance;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final readonly class AssetLeaseRelatedPresenter
{
    public function __construct(
        private AssetLeaseBalance $balances,
        private ResourcePresenter $resources,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        return [
            $this->leases($data),
            $this->collections($data),
        ];
    }

    /** @return array<string, mixed> */
    private function leases(AssetDetailData $data): array
    {
        $leaseLabel = trans('app.assets.lease');
        $assetLabel = trans('app.assets.unit_or_space');
        $tenant = trans('app.assets.tenant');
        $status = trans('app.assets.status');
        $balance = trans('app.assets.balance');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.leases'),
            'description' => trans('app.assets.property_leases_help'),
            'columns' => [$leaseLabel, $assetLabel, $tenant, $status, $balance, $open],
            'rows' => $data->operations->leases->map(fn (Lease $lease): array => [
                $leaseLabel => $lease->code,
                $assetLabel => $this->resources->localized(
                    data_get($lease, 'leaseable.title_en'),
                    data_get($lease, 'leaseable.title_ar'),
                ) ?: '-',
                $tenant => data_get($lease, 'tenantProfile.user.name', '-'),
                $status => trans("app.status.{$lease->status}"),
                $balance => $this->money($this->balances->remaining($lease), $lease->currency),
                $open => ['label' => $open, 'href' => route('leases.show', $lease)],
            ])->all(),
            'emptyText' => trans('app.assets.no_property_leases'),
            'actionHref' => $this->leaseActionHref($data),
            'actionLabel' => $this->leaseActionLabel($data),
        ];
    }

    /** @return array<string, mixed> */
    private function collections(AssetDetailData $data): array
    {
        $leaseLabel = trans('app.assets.lease');
        $tenant = trans('app.assets.tenant');
        $dueDate = trans('app.assets.due_date');
        $remaining = trans('app.assets.remaining');
        $timing = trans('app.assets.timing');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.collection_queue'),
            'description' => trans('app.assets.collection_queue_help'),
            'columns' => [$leaseLabel, $tenant, $dueDate, $remaining, $timing, $open],
            'rows' => $data->operations->collectionQueue->map(
                fn (LeaseInstallment $installment): array => [
                    $leaseLabel => $installment->lease?->code ?: '-',
                    $tenant => data_get($installment, 'lease.tenantProfile.user.name', '-'),
                    $dueDate => $installment->due_date?->toDateString() ?: '-',
                    $remaining => $this->money(
                        $installment->remaining_amount,
                        $installment->lease?->currency ?: $data->asset->currency,
                    ),
                    $timing => $this->timing($installment),
                    $open => [
                        'label' => $open,
                        'href' => $installment->lease
                            ? route('leases.show', $installment->lease)
                            : route('reports.index', [
                                'property_id' => $data->operations->propertyRoot->id,
                            ]),
                    ],
                ],
            )->all(),
            'emptyText' => trans('app.assets.no_collection_work'),
            'actionHref' => PortfolioModules::enabledForUser($data->actor, 'reports')
                ? route('reports.index', [
                    'property_id' => $data->operations->propertyRoot->id,
                ])
                : route('leases.index', [
                    'property_id' => $data->operations->propertyRoot->id,
                ]),
            'actionLabel' => trans('app.assets.review_collections'),
        ];
    }

    private function timing(LeaseInstallment $installment): string
    {
        if (! $installment->due_date) {
            return '-';
        }

        $days = (int) today()->diffInDays($installment->due_date, false);

        if ($days < 0) {
            return trans('app.assets.days_overdue', ['count' => abs($days)]);
        }

        return trans('app.assets.due_in_days', ['count' => $days]);
    }

    private function leaseActionHref(AssetDetailData $data): string
    {
        if ($data->operations->directActiveLease) {
            return route('leases.show', $data->operations->directActiveLease);
        }

        if ($data->asset->rentable) {
            return route('leases.create', ['asset_id' => $data->asset->id]);
        }

        return route('assets.index', [
            'property_id' => $data->operations->propertyRoot->id,
            'rentable' => 'yes',
            'occupancy_status' => 'vacant',
        ]);
    }

    private function leaseActionLabel(AssetDetailData $data): string
    {
        if ($data->operations->directActiveLease) {
            return trans('app.assets.open_lease');
        }

        return trans($data->asset->rentable
            ? 'app.assets.create_lease'
            : 'app.assets.choose_vacant_space');
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
