<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Shared\PortfolioScope;

final readonly class RentRollRowPresenter
{
    public function __construct(private PortfolioScope $portfolios) {}

    /**
     * @param  array<int, int>  $rootByAsset
     * @param  array<int, Asset>  $assetsById
     * @return array<string, mixed>
     */
    public function present(Asset $asset, array $rootByAsset, array $assetsById): array
    {
        /** @var Lease|null $lease */
        $lease = $asset->leases->first();
        $root = isset($rootByAsset[$asset->id])
            ? ($assetsById[$rootByAsset[$asset->id]] ?? null)
            : null;
        $overdue = $lease ? $this->overdue($lease) : 0.0;
        $daysRemaining = $lease?->ends_at
            ? (int) now()->startOfDay()->diffInDays($lease->ends_at, false)
            : null;
        $state = $lease === null
            ? 'vacant'
            : ($overdue > 0
                ? 'arrears'
                : ($daysRemaining !== null && $daysRemaining <= 90 ? 'expiring' : 'occupied'));

        return [
            'id' => $asset->id,
            'code' => $asset->code,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'asset_type' => $asset->asset_type,
            'usage_type' => $asset->usage_type,
            'state' => $state,
            'hierarchy' => $this->hierarchy($asset, $assetsById),
            'property' => $root ? [
                'id' => $root->id,
                'code' => $root->code,
                'title_en' => $root->title_en,
                'title_ar' => $root->title_ar,
            ] : null,
            'portfolio' => [
                'id' => $asset->portfolio_id,
                'name' => $this->portfolios->localized(
                    $asset->portfolio?->name_en,
                    $asset->portfolio?->name_ar,
                ),
            ],
            'lease' => $lease ? $this->lease($lease, $overdue, $daysRemaining) : null,
            'links' => [
                'asset' => route('assets.show', $asset, false),
                'lease' => $lease ? route('leases.show', $lease, false) : null,
            ],
            'is_showcase' => (bool) $asset->is_showcase,
        ];
    }

    /** @return array<string, mixed> */
    private function lease(Lease $lease, float $overdue, ?int $daysRemaining): array
    {
        $due = (float) ($lease->getAttribute('installments_total_due') ?? 0);
        $paid = (float) ($lease->getAttribute('installments_total_paid') ?? 0);

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'tenant' => $lease->tenantProfile?->user?->name
                ?: $lease->tenantProfile?->company_name
                ?: trans('app.reports.rent_roll_unknown_tenant'),
            'status' => $lease->status,
            'payment_frequency' => $lease->payment_frequency,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'days_remaining' => $daysRemaining,
            'rent_amount' => (float) $lease->rent_amount,
            'deposit_amount' => (float) $lease->deposit_amount,
            'currency' => $lease->currency ?: 'SAR',
            'total_due' => $due,
            'total_paid' => $paid,
            'balance' => max(0, $due - $paid),
            'overdue' => $overdue,
        ];
    }

    private function overdue(Lease $lease): float
    {
        $due = (float) ($lease->getAttribute('installments_overdue_due') ?? 0);
        $paid = (float) ($lease->getAttribute('installments_overdue_paid') ?? 0);

        return max(0, $due - $paid);
    }

    /**
     * @param  array<int, Asset>  $assetsById
     * @return list<array{id:int,title_en:string,title_ar:string,code:string}>
     */
    private function hierarchy(Asset $asset, array $assetsById): array
    {
        $path = [];
        $current = $assetsById[$asset->id] ?? $asset;
        $visited = [];

        while ($current instanceof Asset && ! isset($visited[$current->id])) {
            $visited[$current->id] = true;
            array_unshift($path, [
                'id' => $current->id,
                'title_en' => $current->title_en,
                'title_ar' => $current->title_ar,
                'code' => $current->code,
            ]);
            $current = $current->parent_id !== null
                ? ($assetsById[$current->parent_id] ?? null)
                : null;
        }

        return $path;
    }
}
