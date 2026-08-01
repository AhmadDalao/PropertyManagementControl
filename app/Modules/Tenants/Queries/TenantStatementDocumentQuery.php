<?php

namespace App\Modules\Tenants\Queries;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\MorphTypes;
use Illuminate\Database\Eloquent\Builder;

final readonly class TenantStatementDocumentQuery
{
    public function __construct(
        private AssignedPropertyScope $assignments,
        private MorphTypes $morphTypes,
    ) {}

    /**
     * @param  array<int, int>  $leaseIds
     * @param  array<int, int>  $paymentIds
     * @return Builder<Document>
     */
    public function handle(User $actor, array $leaseIds, array $paymentIds): Builder
    {
        return $this->assignments
            ->documents(Document::query(), $actor)
            ->where(function (Builder $query) use ($leaseIds, $paymentIds): void {
                $query
                    ->where(function (Builder $leases) use ($leaseIds): void {
                        $leases
                            ->whereIn('documentable_type', $this->morphTypes->for(new Lease))
                            ->whereIn('documentable_id', $leaseIds);
                    })
                    ->orWhere(function (Builder $payments) use ($paymentIds): void {
                        $payments
                            ->whereIn('documentable_type', $this->morphTypes->for(new Payment))
                            ->whereIn('documentable_id', $paymentIds);
                    });
            });
    }
}
