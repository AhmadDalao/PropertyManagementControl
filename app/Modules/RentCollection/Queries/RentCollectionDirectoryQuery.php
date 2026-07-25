<?php

namespace App\Modules\RentCollection\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\RentCollection\Support\RentCollectionAccess;
use App\Modules\RentCollection\Support\RentCollectionOptions;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\TableQuery;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class RentCollectionDirectoryQuery
{
    public function __construct(
        private RentCollectionAccess $access,
        private PropertyScope $properties,
        private TableQuery $tables,
        private AssignedPropertyScope $assignments,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'actionable',
            'line_type' => 'all',
            'date_from' => '',
            'date_to' => '',
            'property_id' => 'all',
            'sort' => 'due_date',
            'direction' => 'asc',
        ]);

        if (! in_array($filters['status'], ['all', ...RentCollectionOptions::STATUSES], true)) {
            $filters['status'] = 'actionable';
        }

        if (! in_array($filters['line_type'], ['all', ...RentCollectionOptions::LINE_TYPES], true)) {
            $filters['line_type'] = 'all';
        }

        foreach (['date_from', 'date_to'] as $field) {
            if (! $this->validDate((string) $filters[$field])) {
                $filters[$field] = '';
            }
        }

        return $filters;
    }

    /** @return Builder<LeaseInstallment> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);
        $leaseIds = $this->assignments
            ->leases(Lease::query(), $actor)
            ->select('id');

        return LeaseInstallment::query()->whereHas(
            'lease',
            fn (Builder $leases) => $leases
                ->when(
                    ! $actor->hasRole('superadmin'),
                    fn (Builder $scoped) => $scoped->where('portfolio_id', $actor->portfolio_id),
                )
                ->whereIn('id', clone $leaseIds)
                ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
                ->whereIn('leaseable_type', $this->properties->leaseableTypes()),
        );
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @return Builder<LeaseInstallment>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'lease_id',
                'sequence',
                'line_type',
                'label',
                'period_start',
                'period_end',
                'due_date',
                'amount_due',
                'amount_paid',
                'status',
                'paid_at',
                'created_at',
            ])
            ->with([
                'lease:id,portfolio_id,tenant_profile_id,leaseable_type,leaseable_id,code,status,currency',
                'lease.tenantProfile:id,user_id',
                'lease.tenantProfile.user:id,name,email,phone',
                'lease.leaseable',
            ]);
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $actor): void
    {
        $this->applyScope($query, $filters, $actor);
        $this->applyStatus($query, (string) $filters['status']);
        $this->tables->exact($query, $filters, 'line_type');
        $this->tables->dateRange($query, $filters, 'due_date');
        $this->tables->search($query, (string) $filters['search'], [
            'label',
            'notes',
            fn (Builder $installments, string $search, string $like) => $installments->orWhereHas(
                'lease',
                fn (Builder $leases) => $leases->where('code', 'like', $like),
            ),
            fn (Builder $installments, string $search, string $like) => $installments->orWhereHas(
                'lease.tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like),
            ),
            fn (Builder $installments, string $search, string $like) => $installments->orWhereHas(
                'lease',
                fn (Builder $leases) => $leases->whereIn(
                    'leaseable_id',
                    Asset::query()
                        ->select('id')
                        ->where(function (Builder $assets) use ($like): void {
                            $assets
                                ->where('title_en', 'like', $like)
                                ->orWhere('title_ar', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        }),
                ),
            ),
        ]);
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyScope(Builder $query, array $filters, User $actor): void
    {
        if ($filters['portfolio_id'] !== null) {
            $query->whereHas(
                'lease',
                fn (Builder $leases) => $leases->where('portfolio_id', $filters['portfolio_id']),
            );
        }

        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        if ($assetIds !== null) {
            $query->whereHas(
                'lease',
                fn (Builder $leases) => $leases->whereIn('leaseable_id', $assetIds),
            );
        }
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     */
    public function applyStatus(Builder $query, string $status): void
    {
        match ($status) {
            'actionable' => $query
                ->whereColumn('amount_paid', '<', 'amount_due')
                ->whereDate('due_date', '<=', today()->addDays(30)),
            'open' => $query->whereColumn('amount_paid', '<', 'amount_due'),
            'overdue' => $query
                ->whereColumn('amount_paid', '<', 'amount_due')
                ->whereDate('due_date', '<', today()),
            'partial' => $query
                ->where('amount_paid', '>', 0)
                ->whereColumn('amount_paid', '<', 'amount_due'),
            'paid' => $query->whereColumn('amount_paid', '>=', 'amount_due'),
            default => null,
        };
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
