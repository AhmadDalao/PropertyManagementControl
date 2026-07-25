<?php

namespace App\Modules\LeaseRenewals\Queries;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\LeaseRenewals\Support\LeaseRenewalNoticeScope;
use App\Modules\LeaseRenewals\Support\LeaseRenewalOptions;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class LeaseRenewalDirectoryQuery
{
    public function __construct(
        private LeaseAccess $access,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
        private TableQuery $tables,
        private LeaseRenewalSearch $search,
        private LeaseRenewalNoticeScope $notices,
        private AssignedPropertyScope $assignments,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, LeaseRenewalOptions::DEFAULT_FILTERS);

        if (! in_array($filters['queue'], ['all', ...LeaseRenewalOptions::QUEUES], true)) {
            $filters['queue'] = 'attention';
        }

        if (! in_array($filters['horizon'], LeaseRenewalOptions::HORIZONS, true)) {
            $filters['horizon'] = '90';
        }

        if (! in_array($filters['lease_status'], ['all', ...LeaseRenewalOptions::LEASE_STATUSES], true)) {
            $filters['lease_status'] = 'all';
        }

        return $filters;
    }

    /** @return Builder<Lease> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->assignments
            ->leases($this->portfolios->apply(Lease::query(), $actor), $actor)
            ->whereIn('status', LeaseRenewalOptions::LEASE_STATUSES)
            ->whereDate('ends_at', '>=', today()->subDays(90));
    }

    /**
     * @param  Builder<Lease>  $query
     * @return Builder<Lease>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'tenant_profile_id',
                'managed_by_user_id',
                'leaseable_type',
                'leaseable_id',
                'code',
                'status',
                'payment_frequency',
                'started_at',
                'ends_at',
                'renewal_notice_days',
                'rent_amount',
                'currency',
                'created_at',
            ])
            ->with([
                'tenantProfile:id,user_id',
                'tenantProfile.user:id,name,email,phone',
                'managedBy:id,name',
                'portfolio:id,name_en,name_ar',
                'leaseable',
                'renewalLease:id,renewed_from_lease_id,code,status,started_at,ends_at',
            ])
            ->withSum('installments as installments_total_due', 'amount_due')
            ->withSum('installments as installments_total_paid', 'amount_paid')
            ->withCount([
                'installments as overdue_installments_count' => fn (Builder $installments) => $installments
                    ->whereColumn('amount_paid', '<', 'amount_due')
                    ->whereDate('due_date', '<', today()),
            ]);
    }

    /**
     * @param  Builder<Lease>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $actor): void
    {
        $this->applyContext($query, $filters, $actor);
        $this->applyQueue($query, (string) $filters['queue']);
        $this->search->apply($query, (string) $filters['search']);
    }

    /**
     * @param  Builder<Lease>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyContext(Builder $query, array $filters, User $actor): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');

        if ($filters['lease_status'] !== 'all') {
            $query->where('status', $filters['lease_status']);
        }

        if ($filters['horizon'] !== 'all') {
            $query->whereDate('ends_at', '<=', today()->addDays((int) $filters['horizon']));
        }

        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        if ($assetIds !== null) {
            $query
                ->whereIn('leaseable_type', $this->properties->leaseableTypes())
                ->whereIn('leaseable_id', $assetIds);
        }
    }

    /** @param Builder<Lease> $query */
    public function applyQueue(Builder $query, string $queue): void
    {
        match ($queue) {
            'attention' => $query
                ->whereDoesntHave('renewalLease')
                ->where(function (Builder $leases): void {
                    $leases
                        ->where('status', 'expired')
                        ->orWhere(function (Builder $active): void {
                            $active->where('status', 'active');
                            $this->notices->apply($active, due: true);
                        });
                }),
            'upcoming' => $query
                ->where('status', 'active')
                ->whereDoesntHave('renewalLease')
                ->where(function (Builder $active): void {
                    $this->notices->apply($active, due: false);
                }),
            'prepared' => $query->whereHas('renewalLease'),
            'expired' => $query
                ->where('status', 'expired')
                ->whereDoesntHave('renewalLease'),
            default => null,
        };
    }
}
