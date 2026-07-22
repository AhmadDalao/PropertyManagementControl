<?php

namespace App\Modules\Leases\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class LeaseDirectoryQuery
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly MorphTypes $morphTypes,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'payment_frequency' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);

        if (! in_array($filters['status'], ['all', ...LeaseOptions::STATUSES], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['payment_frequency'], ['all', ...LeaseOptions::PAYMENT_FREQUENCIES], true)) {
            $filters['payment_frequency'] = 'all';
        }

        foreach (['date_from', 'date_to'] as $field) {
            if (! $this->validDate((string) $filters[$field])) {
                $filters[$field] = '';
            }
        }

        return $filters;
    }

    /** @return Builder<Lease> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->portfolios->apply(Lease::query(), $actor);
    }

    /**
     * @param  Builder<Lease>  $query
     * @return Builder<Lease>
     */
    public function listing(Builder $query): Builder
    {
        $nextDueDate = LeaseInstallment::query()
            ->select('due_date')
            ->whereColumn('lease_id', 'leases.id')
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->orderBy('due_date')
            ->orderBy('sequence')
            ->limit(1);
        $nextDueAmount = LeaseInstallment::query()
            ->selectRaw('amount_due - amount_paid')
            ->whereColumn('lease_id', 'leases.id')
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->orderBy('due_date')
            ->orderBy('sequence')
            ->limit(1);

        return $query
            ->select([
                'id',
                'portfolio_id',
                'tenant_profile_id',
                'leaseable_type',
                'leaseable_id',
                'code',
                'status',
                'payment_frequency',
                'started_at',
                'ends_at',
                'signed_at',
                'rent_amount',
                'deposit_amount',
                'tax_amount',
                'discount_amount',
                'currency',
                'billing_day',
                'created_at',
            ])
            ->with([
                'tenantProfile:id,user_id',
                'tenantProfile.user:id,name',
                'leaseable',
            ])
            ->withSum('installments as installments_total_due', 'amount_due')
            ->withSum('installments as installments_total_paid', 'amount_paid')
            ->withCount([
                'installments',
                'installments as overdue_installments_count' => fn (Builder $installments) => $installments
                    ->whereColumn('amount_paid', '<', 'amount_due')
                    ->whereDate('due_date', '<', today()),
                'installments as open_installments_count' => fn (Builder $installments) => $installments
                    ->whereColumn('amount_paid', '<', 'amount_due'),
            ])
            ->addSelect([
                'next_due_date' => $nextDueDate,
                'next_due_amount' => $nextDueAmount,
            ]);
    }

    /**
     * @param  Builder<Lease>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'payment_frequency'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->dateRange($query, $filters, 'started_at');
        $this->tables->search($query, (string) $filters['search'], [
            'code',
            'notes',
            fn (Builder $leases, string $search, string $like) => $leases->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
            fn (Builder $leases, string $search, string $like) => $leases->orWhere(function (Builder $assets) use ($like): void {
                $assets
                    ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                    ->whereIn('leaseable_id', Asset::query()
                        ->select('id')
                        ->where(function (Builder $titles) use ($like): void {
                            $titles->where('title_en', 'like', $like)
                                ->orWhere('title_ar', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        }));
            }),
        ]);
    }

    /**
     * @param  Builder<Lease>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyPortfolio(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
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
