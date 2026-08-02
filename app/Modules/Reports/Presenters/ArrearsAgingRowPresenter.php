<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Models\LeaseInstallment;
use App\Modules\RentCollection\Presenters\RentCollectionRowPresenter;
use App\Modules\Reports\Support\ArrearsAgingOptions;

final readonly class ArrearsAgingRowPresenter
{
    public function __construct(
        private RentCollectionRowPresenter $rows,
    ) {}

    /** @return array<string, mixed> */
    public function present(LeaseInstallment $installment, ?Asset $property): array
    {
        $record = $this->rows->present($installment, $property);
        $lease = $installment->lease;
        $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            ...$record,
            'bucket' => ArrearsAgingOptions::bucketFor((int) $record['days_overdue']),
            'links' => [
                'follow_up' => route('rent-collection.follow-up', $installment, false),
                'lease' => $lease ? route('leases.show', $lease, false) : null,
                'asset' => $asset ? route('assets.show', $asset, false) : null,
            ],
        ];
    }
}
