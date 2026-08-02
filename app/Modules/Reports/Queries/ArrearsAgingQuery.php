<?php

namespace App\Modules\Reports\Queries;

use App\Models\Asset;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Assets\Support\AssetPropertyContext;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\RentCollection\Queries\RentCollectionDirectoryQuery;
use App\Modules\Reports\Presenters\ArrearsAgingRowPresenter;
use App\Modules\Reports\Presenters\ArrearsAgingScopePresenter;
use App\Modules\Reports\Support\ArrearsAgingOptions;
use App\Modules\Reports\Support\ArrearsAgingScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;

final readonly class ArrearsAgingQuery
{
    public function __construct(
        private ArrearsAgingScope $scope,
        private ArrearsAgingMetricsQuery $metrics,
        private RentCollectionDirectoryQuery $directory,
        private ArrearsAgingRowPresenter $rows,
        private ArrearsAgingScopePresenter $scopePresenter,
        private AssetPropertyContext $assetContext,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
        private TableQuery $tables,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters): array
    {
        $base = $this->scope->query($actor, $filters);
        $selected = clone $base;
        $this->scope->applyBucket($selected, (string) $filters['bucket']);
        $options = $this->options($actor);
        $context = $this->assetContext->get($actor, $filters['portfolio_id']);
        $records = $this->tables->paginate(
            $this->directory->listing(clone $selected),
            $filters,
            ['due_date', 'amount_due', 'amount_paid', 'created_at'],
            'due_date',
        );

        return [
            'records' => $records->through(
                fn (LeaseInstallment $installment): array => $this->row($installment, ...$context),
            ),
            'filters' => $filters,
            'counts' => $this->metrics->counts($base, (string) $filters['bucket']),
            'insights' => $this->metrics->insights($selected),
            'currencyPositions' => $this->metrics->currencyPositions($selected),
            'scope' => $this->scopePresenter->present($actor, $filters, ...$options),
            'portfolioOptions' => $options[0],
            'propertyOptions' => $options[1],
            'bucketOptions' => ['all', ...ArrearsAgingOptions::BUCKETS],
            'downloads' => $this->downloads($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function export(User $actor, array $filters): array
    {
        $query = $this->scope->query($actor, $filters);
        $this->scope->applyBucket($query, (string) $filters['bucket']);
        $options = $this->options($actor);
        $context = $this->assetContext->get($actor, $filters['portfolio_id']);
        $records = $this->directory->listing(clone $query)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->map(fn (LeaseInstallment $installment): array => $this->row($installment, ...$context))
            ->all();

        return [
            'filters' => $filters,
            'records' => $records,
            'insights' => $this->metrics->insights($query),
            'currencyPositions' => $this->metrics->currencyPositions($query),
            'scope' => $this->scopePresenter->present($actor, $filters, ...$options),
        ];
    }

    /**
     * @return array{
     *     0:array<int, array{id:int,name:string}>,
     *     1:array<int, array{id:int,portfolio_id:int,name:string}>
     * }
     */
    private function options(User $actor): array
    {
        return [
            $this->portfolios->options($actor),
            $this->properties->options($actor),
        ];
    }

    /**
     * @param  array<int, int>  $rootByAsset
     * @param  array<int, Asset>  $assetsById
     * @return array<string, mixed>
     */
    private function row(
        LeaseInstallment $installment,
        array $rootByAsset,
        array $assetsById,
    ): array {
        $assetId = $installment->lease?->leaseable instanceof Asset
            ? $installment->lease->leaseable->id
            : null;
        $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;

        return $this->rows->present(
            $installment,
            $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{pdf:string,docx:string,xlsx:string}
     */
    private function downloads(array $filters): array
    {
        $query = array_filter([
            'search' => $filters['search'],
            'bucket' => $filters['bucket'] !== 'all' ? $filters['bucket'] : null,
            'portfolio_id' => $filters['portfolio_id'],
            'property_id' => $filters['property_id'],
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            'pdf' => route('reports.arrears-aging.pdf', $query, false),
            'docx' => route('reports.arrears-aging.word', $query, false),
            'xlsx' => route('reports.arrears-aging.workbook', $query, false),
        ];
    }
}
