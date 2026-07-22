<?php

namespace App\Modules\Payments\Queries;

use App\Models\Asset;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class PaymentDirectoryQuery
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly MorphTypes $morphTypes,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'type' => 'all',
            'method' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);

        foreach ([
            'status' => PaymentOptions::STATUSES,
            'type' => PaymentOptions::TYPES,
            'method' => PaymentOptions::METHODS,
        ] as $field => $allowed) {
            if (! in_array($filters[$field], ['all', ...$allowed], true)) {
                $filters[$field] = 'all';
            }
        }

        foreach (['date_from', 'date_to'] as $field) {
            if (! $this->validDate((string) $filters[$field])) {
                $filters[$field] = '';
            }
        }

        return $filters;
    }

    /** @return Builder<Payment> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->portfolios->apply(Payment::query(), $actor);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'lease_id',
                'tenant_profile_id',
                'reference',
                'type',
                'method',
                'status',
                'received_on',
                'amount',
                'currency',
                'created_at',
            ])
            ->with([
                'lease:id,portfolio_id,tenant_profile_id,leaseable_type,leaseable_id,code,status,currency',
                'lease.leaseable',
                'tenantProfile:id,user_id',
                'tenantProfile.user:id,name',
            ])
            ->withCount('allocations')
            ->withSum('allocations', 'amount');
    }

    /**
     * @param  Builder<Payment>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'type', 'method'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->dateRange($query, $filters, 'received_on');
        $this->tables->search($query, (string) $filters['search'], [
            'reference',
            'notes',
            fn (Builder $payments, string $search, string $like) => $payments->orWhereHas(
                'lease',
                fn (Builder $leases) => $leases->where('code', 'like', $like),
            ),
            fn (Builder $payments, string $search, string $like) => $payments->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
            fn (Builder $payments, string $search, string $like) => $payments->orWhereHas(
                'lease',
                fn (Builder $leases) => $leases->where(function (Builder $assets) use ($like): void {
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
            ),
        ]);
    }

    /**
     * @param  Builder<Payment>  $query
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
