<?php

namespace App\Modules\LeaseMoveOuts\Queries;

use App\Models\Lease;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutOptions;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class LeaseMoveOutDirectoryQuery
{
    public function __construct(
        private LeaseAccess $access,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
        private AssignedPropertyScope $assignments,
        private TableQuery $tables,
        private LeaseMoveOutSearch $search,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'queue' => 'attention',
            'horizon' => '90',
            'property_id' => 'all',
            'sort' => 'move_out_date',
            'direction' => 'asc',
        ]);

        if (! in_array($filters['queue'], ['all', ...LeaseMoveOutOptions::QUEUES], true)) {
            $filters['queue'] = 'attention';
        }

        if (! in_array($filters['horizon'], LeaseMoveOutOptions::HORIZONS, true)) {
            $filters['horizon'] = '90';
        }

        return $filters;
    }

    /** @return Builder<LeaseMoveOut> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->portfolios
            ->apply(LeaseMoveOut::query(), $actor)
            ->whereIn(
                'lease_id',
                $this->assignments->leases(Lease::query(), $actor)->select('id'),
            );
    }

    /** @param Builder<LeaseMoveOut> $query
     * @return Builder<LeaseMoveOut>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'lease_id',
                'status',
                'move_out_date',
                'reason',
                'deposit_disposition',
                'deposit_deduction_amount',
                'keys_returned',
                'balance_at_completion',
                'completed_at',
                'created_at',
            ])
            ->with([
                'lease' => fn ($leases) => $leases
                    ->select([
                        'id',
                        'portfolio_id',
                        'tenant_profile_id',
                        'managed_by_user_id',
                        'leaseable_type',
                        'leaseable_id',
                        'code',
                        'status',
                        'deposit_amount',
                        'currency',
                        'started_at',
                        'ends_at',
                    ])
                    ->with([
                        'tenantProfile:id,user_id',
                        'tenantProfile.user:id,name,email,phone',
                        'managedBy:id,name',
                        'portfolio:id,name_en,name_ar',
                        'leaseable',
                        'documents' => fn ($documents) => $documents
                            ->whereIn('type', LeaseMoveOutOptions::REQUIRED_DOCUMENT_TYPES)
                            ->select(['id', 'documentable_type', 'documentable_id', 'type']),
                    ])
                    ->withSum('installments as move_out_total_due', 'amount_due')
                    ->withSum('installments as move_out_total_paid', 'amount_paid'),
            ]);
    }

    /**
     * @param  Builder<LeaseMoveOut>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $actor): void
    {
        $this->applyContext($query, $filters, $actor);
        $this->applyQueue($query, (string) $filters['queue']);
        $this->search->apply($query, (string) $filters['search']);
    }

    /**
     * @param  Builder<LeaseMoveOut>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyContext(Builder $query, array $filters, User $actor): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');

        if ($filters['horizon'] !== 'all') {
            $query->whereDate('move_out_date', '<=', today()->addDays((int) $filters['horizon']));
        }

        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        if ($assetIds !== null) {
            $query->whereHas('lease', fn (Builder $leases) => $leases
                ->whereIn('leaseable_type', $this->properties->leaseableTypes())
                ->whereIn('leaseable_id', $assetIds));
        }
    }

    /** @param Builder<LeaseMoveOut> $query */
    public function applyQueue(Builder $query, string $queue): void
    {
        match ($queue) {
            'attention' => $query
                ->where('status', 'planned')
                ->whereDate('move_out_date', '<=', today())
                ->where(fn (Builder $items) => $this->missingRequirement($items)),
            'upcoming' => $query
                ->where('status', 'planned')
                ->whereDate('move_out_date', '>', today()),
            'ready' => $this->ready($query),
            'completed', 'cancelled' => $query->where('status', $queue),
            default => null,
        };
    }

    /**
     * @param  Builder<LeaseMoveOut>  $query
     * @return Builder<LeaseMoveOut>
     */
    private function ready(Builder $query): Builder
    {
        return $query
            ->where('status', 'planned')
            ->whereDate('move_out_date', '<=', today())
            ->where('keys_returned', true)
            ->where('deposit_disposition', '!=', 'pending')
            ->whereHas('lease.documents', fn (Builder $documents) => $documents
                ->where('type', 'termination_notice'))
            ->whereHas('lease.documents', fn (Builder $documents) => $documents
                ->where('type', 'move_out_inspection'));
    }

    /** @param Builder<LeaseMoveOut> $query */
    private function missingRequirement(Builder $query): void
    {
        $query
            ->where('keys_returned', false)
            ->orWhere('deposit_disposition', 'pending')
            ->orWhereDoesntHave('lease.documents', fn (Builder $documents) => $documents
                ->where('type', 'termination_notice'))
            ->orWhereDoesntHave('lease.documents', fn (Builder $documents) => $documents
                ->where('type', 'move_out_inspection'));
    }
}
