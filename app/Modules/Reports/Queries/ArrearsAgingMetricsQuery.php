<?php

namespace App\Modules\Reports\Queries;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Modules\Reports\Support\ArrearsAgingOptions;
use App\Modules\Reports\Support\ArrearsAgingScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class ArrearsAgingMetricsQuery
{
    public function __construct(private ArrearsAgingScope $scope) {}

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @return array<int, array{label:string,value:int,filter:array<string,string>,active:bool}>
     */
    public function counts(Builder $query, string $activeBucket): array
    {
        return collect(['all', ...ArrearsAgingOptions::BUCKETS])
            ->map(function (string $bucket) use ($query, $activeBucket): array {
                $bucketQuery = clone $query;
                $this->scope->applyBucket($bucketQuery, $bucket);

                return [
                    'label' => trans("app.reports.aging_bucket_{$bucket}"),
                    'value' => $bucketQuery->count(),
                    'filter' => ['bucket' => $bucket],
                    'active' => $activeBucket === $bucket,
                ];
            })
            ->all();
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @return array{installments:int,leases:int,tenants:int,oldest_days:int}
     */
    public function insights(Builder $query): array
    {
        $oldestDue = (clone $query)->min('due_date');

        return [
            'installments' => (clone $query)->count(),
            'leases' => (clone $query)->distinct()->count('lease_id'),
            'tenants' => Lease::query()
                ->whereIn('id', (clone $query)->select('lease_id'))
                ->distinct()
                ->count('tenant_profile_id'),
            'oldest_days' => is_string($oldestDue)
                ? (int) CarbonImmutable::parse($oldestDue)->diffInDays(today())
                : 0,
        ];
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @return list<array<string, int|float|string>>
     */
    public function currencyPositions(Builder $query): array
    {
        $day30 = today()->subDays(30)->toDateString();
        $day60 = today()->subDays(60)->toDateString();
        $day90 = today()->subDays(90)->toDateString();
        $balance = '(lease_installments.amount_due - lease_installments.amount_paid)';

        $positions = (clone $query)
            ->selectRaw('lease_id')
            ->selectRaw('COUNT(*) as installment_count')
            ->selectRaw("SUM({$balance}) as total")
            ->selectRaw(
                "SUM(CASE WHEN lease_installments.due_date >= ? THEN {$balance} ELSE 0 END) as days_1_30",
                [$day30],
            )
            ->selectRaw(
                "SUM(CASE WHEN lease_installments.due_date < ? AND lease_installments.due_date >= ? THEN {$balance} ELSE 0 END) as days_31_60",
                [$day30, $day60],
            )
            ->selectRaw(
                "SUM(CASE WHEN lease_installments.due_date < ? AND lease_installments.due_date >= ? THEN {$balance} ELSE 0 END) as days_61_90",
                [$day60, $day90],
            )
            ->selectRaw(
                "SUM(CASE WHEN lease_installments.due_date < ? THEN {$balance} ELSE 0 END) as over_90",
                [$day90],
            )
            ->groupBy('lease_id')
            ->get();
        $currencies = Lease::query()
            ->whereIn('id', $positions->pluck('lease_id'))
            ->pluck('currency', 'id');

        $result = [];

        foreach ($positions
            ->groupBy(fn (LeaseInstallment $position): string => (string) (
                $currencies->get($position->lease_id) ?: 'SAR'
            )) as $currency => $leases) {
            $result[] = [
                'currency' => (string) $currency,
                'installment_count' => (int) $leases->sum('installment_count'),
                'lease_count' => $leases->count(),
                'total' => (float) $leases->sum('total'),
                'days_1_30' => (float) $leases->sum('days_1_30'),
                'days_31_60' => (float) $leases->sum('days_31_60'),
                'days_61_90' => (float) $leases->sum('days_61_90'),
                'over_90' => (float) $leases->sum('over_90'),
            ];
        }

        usort(
            $result,
            static fn (array $left, array $right): int => $left['currency'] <=> $right['currency'],
        );

        return $result;
    }
}
