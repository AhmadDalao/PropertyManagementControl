<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ExpenseDirectoryQuery
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly PropertyScope $properties,
        private readonly TableQuery $tables,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'category' => 'all',
            'date_from' => '',
            'date_to' => '',
            'property_id' => 'all',
        ]);

        if (! in_array($filters['status'], ['all', ...ExpenseOptions::STATUSES], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['category'], ['all', ...ExpenseOptions::CATEGORIES], true)) {
            $filters['category'] = 'all';
        }

        foreach (['date_from', 'date_to'] as $field) {
            if (! $this->validDate((string) $filters[$field])) {
                $filters[$field] = '';
            }
        }

        return $filters;
    }

    /** @return Builder<ExpenseEntry> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->assignments->expenses(
            $this->portfolios->apply(ExpenseEntry::query(), $actor),
            $actor,
        );
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @return Builder<ExpenseEntry>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'asset_id',
                'maintenance_request_id',
                'title',
                'category',
                'status',
                'vendor_name',
                'amount',
                'currency',
                'incurred_on',
                'created_at',
            ])
            ->with([
                'asset:id,portfolio_id,title_en,title_ar,code',
                'maintenanceRequest:id,portfolio_id,asset_id,title,status,priority',
            ]);
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $actor): void
    {
        foreach (['portfolio_id', 'status', 'category'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->dateRange($query, $filters, 'incurred_on');
        $this->applyProperty($query, $filters, $actor);
        $this->tables->search($query, (string) $filters['search'], [
            'title',
            'description',
            'category',
            'vendor_name',
            fn (Builder $expenses, string $search, string $like) => $expenses->orWhereHas(
                'asset',
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $expenses, string $search, string $like) => $expenses->orWhereHas(
                'maintenanceRequest',
                fn (Builder $requests) => $requests->where('title', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyScope(Builder $query, array $filters, User $actor): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
        $this->applyProperty($query, $filters, $actor);
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyProperty(Builder $query, array $filters, User $actor): void
    {
        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        if ($assetIds !== null) {
            $query->where(function (Builder $expenses) use ($assetIds): void {
                $expenses
                    ->whereIn('asset_id', $assetIds)
                    ->orWhereHas('lease', fn (Builder $leases) => $leases
                        ->whereIn('leaseable_type', $this->properties->leaseableTypes())
                        ->whereIn('leaseable_id', $assetIds))
                    ->orWhereHas('maintenanceRequest', fn (Builder $requests) => $requests
                        ->whereIn('asset_id', $assetIds));
            });
        }
    }

    private function validDate(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
