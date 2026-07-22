<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Models\Payment;
use App\Modules\Reports\Data\PortfolioReportData;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Collection;

final readonly class ReportPaymentRowsPresenter
{
    public function __construct(private PortfolioScope $portfolios) {}

    /** @return array{topAssets:array<int, array<string, mixed>>,recentPayments:array<int, array<string, mixed>>} */
    public function present(PortfolioReportData $data): array
    {
        return [
            'topAssets' => $this->topAssetsByRevenue($data->payments),
            'recentPayments' => $data->payments
                ->sortByDesc('received_on')
                ->take(8)
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'reference' => $payment->reference ?: '#'.$payment->id,
                    'tenant' => $payment->tenantProfile?->user?->name,
                    'lease' => $payment->lease?->code,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'received_on' => $payment->received_on?->toDateString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<int, array<string, mixed>>
     */
    private function topAssetsByRevenue(Collection $payments): array
    {
        /** @var array<int, array{id:int,asset:string,revenue:float,currency:string,lease_ids:array<int|string, bool>}> $assets */
        $assets = [];

        foreach ($payments as $payment) {
            $asset = $payment->lease?->leaseable;

            if (! $asset instanceof Asset) {
                continue;
            }

            $assets[$asset->id] ??= [
                'id' => $asset->id,
                'asset' => $this->portfolios->localized($asset->title_en, $asset->title_ar)
                    ?? trans('app.reports.unknown_asset'),
                'revenue' => 0.0,
                'currency' => $payment->currency,
                'lease_ids' => [],
            ];
            $assets[$asset->id]['revenue'] += (float) $payment->amount;
            $assets[$asset->id]['lease_ids'][$payment->lease_id ?? 'unassigned'] = true;
        }

        return collect($assets)
            ->map(fn (array $asset): array => [
                'id' => $asset['id'],
                'asset' => $asset['asset'],
                'revenue' => $asset['revenue'],
                'currency' => $asset['currency'],
                'lease_count' => count($asset['lease_ids']),
            ])
            ->sortByDesc('revenue')
            ->take(8)
            ->values()
            ->all();
    }
}
