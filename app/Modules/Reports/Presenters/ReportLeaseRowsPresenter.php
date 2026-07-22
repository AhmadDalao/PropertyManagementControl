<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Shared\PortfolioScope;

final readonly class ReportLeaseRowsPresenter
{
    public function __construct(private PortfolioScope $portfolios) {}

    /** @return array<int, array<string, mixed>> */
    public function present(LeaseReportSnapshot $snapshot): array
    {
        return $snapshot->arrearsLeases
            ->take(10)
            ->map(function (array $item): array {
                $lease = $item['lease'];
                $asset = $lease->leaseable;

                return [
                    'id' => $lease->id,
                    'code' => $lease->code,
                    'tenant' => $lease->tenantProfile?->user?->name,
                    'asset' => $asset instanceof Asset
                        ? $this->portfolios->localized($asset->title_en, $asset->title_ar)
                        : null,
                    'ends_at' => $lease->ends_at?->toDateString(),
                    'arrears_amount' => $item['arrears_amount'],
                    'currency' => $lease->currency,
                ];
            })
            ->all();
    }
}
