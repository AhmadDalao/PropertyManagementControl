<?php

namespace App\Modules\Shared\Authorization;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\MorphTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AssignedPropertyScope
{
    public function __construct(
        private readonly ManagerAssetAssignments $assignments,
        private readonly MorphTypes $morphTypes,
    ) {}

    public function restricts(User $actor): bool
    {
        return $this->assignments->restricts($actor);
    }

    /** @return list<int>|null */
    public function assetIds(User $actor): ?array
    {
        return $this->restricts($actor)
            ? $this->assignments->for($actor)['assets']
            : null;
    }

    /** @return list<int>|null */
    public function rootIds(User $actor): ?array
    {
        return $this->restricts($actor)
            ? $this->assignments->for($actor)['roots']
            : null;
    }

    public function hasAssignments(User $actor): bool
    {
        return $this->assignments->hasAny($actor);
    }

    public function ensureHasAssignments(User $actor): void
    {
        abort_unless(
            $this->hasAssignments($actor),
            403,
            trans('app.errors.manager_property_assignment_required'),
        );
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function assets(Builder $query, User $actor, string $column = 'id'): Builder
    {
        return $this->restricts($actor)
            ? $query->whereIn($column, $this->assetIds($actor) ?? [])
            : $query;
    }

    /**
     * @param  Builder<Lease>  $query
     * @return Builder<Lease>
     */
    public function leases(Builder $query, User $actor): Builder
    {
        if (! $this->restricts($actor)) {
            return $query;
        }

        return $query
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->whereIn('leaseable_id', $this->assetIds($actor) ?? []);
    }

    /**
     * @param  Builder<TenantProfile>  $query
     * @return Builder<TenantProfile>
     */
    public function tenants(Builder $query, User $actor): Builder
    {
        if (! $this->restricts($actor)) {
            return $query;
        }

        if (($this->assetIds($actor) ?? []) === []) {
            return $query->whereRaw('1 = 0');
        }

        $assetIds = $this->assetIds($actor) ?? [];

        return $query->where(function (Builder $tenants) use ($actor, $assetIds): void {
            $tenants
                ->whereHas('leases', fn (Builder $leases) => $leases
                    ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                    ->whereIn('leaseable_id', $assetIds))
                ->orWhere(function (Builder $onboarded) use ($actor): void {
                    $onboarded
                        ->where('onboarded_by_user_id', $actor->id)
                        ->whereDoesntHave('leases');
                });
        });
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function payments(Builder $query, User $actor): Builder
    {
        return $this->restricts($actor)
            ? $query->whereIn('lease_id', $this->leaseIds($actor))
            : $query;
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @return Builder<MaintenanceRequest>
     */
    public function maintenance(Builder $query, User $actor): Builder
    {
        return $this->restricts($actor)
            ? $query->whereIn('asset_id', $this->assetIds($actor) ?? [])
            : $query;
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @return Builder<ExpenseEntry>
     */
    public function expenses(Builder $query, User $actor): Builder
    {
        if (! $this->restricts($actor)) {
            return $query;
        }

        $assetIds = $this->assetIds($actor) ?? [];

        return $query->where(function (Builder $expenses) use ($actor, $assetIds): void {
            $expenses
                ->whereIn('asset_id', $assetIds)
                ->orWhereIn('lease_id', $this->leaseIds($actor))
                ->orWhereIn('maintenance_request_id', $this->maintenanceIds($actor));
        });
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function documents(Builder $query, User $actor): Builder
    {
        if (! $this->restricts($actor)) {
            return $query;
        }

        return $query->where(function (Builder $documents) use ($actor): void {
            $documents
                ->where(function (Builder $assets) use ($actor): void {
                    $assets
                        ->whereIn('documentable_type', $this->morphTypes->for(new Asset))
                        ->whereIn('documentable_id', $this->assetIds($actor) ?? []);
                })
                ->orWhere(function (Builder $leases) use ($actor): void {
                    $leases
                        ->whereIn('documentable_type', $this->morphTypes->for(new Lease))
                        ->whereIn('documentable_id', $this->leaseIds($actor));
                })
                ->orWhere(function (Builder $payments) use ($actor): void {
                    $payments
                        ->whereIn('documentable_type', $this->morphTypes->for(new Payment))
                        ->whereIn('documentable_id', $this->paymentIds($actor));
                });
        });
    }

    public function allowsAsset(User $actor, Asset $asset): bool
    {
        return $this->allows($actor, $asset, fn (Builder $query) => $this->assets($query, $actor));
    }

    public function allowsLease(User $actor, Lease $lease): bool
    {
        return $this->allows($actor, $lease, fn (Builder $query) => $this->leases($query, $actor));
    }

    public function allowsTenant(User $actor, TenantProfile $tenant): bool
    {
        return $this->allows($actor, $tenant, fn (Builder $query) => $this->tenants($query, $actor));
    }

    public function allowsPayment(User $actor, Payment $payment): bool
    {
        return $this->allows($actor, $payment, fn (Builder $query) => $this->payments($query, $actor));
    }

    public function allowsMaintenance(User $actor, MaintenanceRequest $request): bool
    {
        return $this->allows($actor, $request, fn (Builder $query) => $this->maintenance($query, $actor));
    }

    public function allowsExpense(User $actor, ExpenseEntry $expense): bool
    {
        return $this->allows($actor, $expense, fn (Builder $query) => $this->expenses($query, $actor));
    }

    public function allowsDocument(User $actor, Document $document): bool
    {
        return $this->allows($actor, $document, fn (Builder $query) => $this->documents($query, $actor));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function records(Builder $query, User $actor): Builder
    {
        if (! $this->restricts($actor)) {
            return $query;
        }

        $model = $query->getModel();

        if ($model instanceof Asset) {
            return $query->whereIn($model->qualifyColumn('id'), $this->assetIds($actor) ?? []);
        }

        if ($model instanceof Lease) {
            return $query
                ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                ->whereIn('leaseable_id', $this->assetIds($actor) ?? []);
        }

        if ($model instanceof Payment) {
            return $query->whereIn('lease_id', $this->leaseIds($actor));
        }

        if ($model instanceof ExpenseEntry) {
            return $query->where(function (Builder $expenses) use ($actor): void {
                $expenses
                    ->whereIn('asset_id', $this->assetIds($actor) ?? [])
                    ->orWhereIn('lease_id', $this->leaseIds($actor))
                    ->orWhereIn('maintenance_request_id', $this->maintenanceIds($actor));
            });
        }

        if ($model instanceof MaintenanceRequest) {
            return $query->whereIn('asset_id', $this->assetIds($actor) ?? []);
        }

        if ($model instanceof Document) {
            return $query->where(function (Builder $documents) use ($actor): void {
                $documents
                    ->where(function (Builder $assets) use ($actor): void {
                        $assets
                            ->whereIn('documentable_type', $this->morphTypes->for(new Asset))
                            ->whereIn('documentable_id', $this->assetIds($actor) ?? []);
                    })
                    ->orWhere(function (Builder $leases) use ($actor): void {
                        $leases
                            ->whereIn('documentable_type', $this->morphTypes->for(new Lease))
                            ->whereIn('documentable_id', $this->leaseIds($actor));
                    })
                    ->orWhere(function (Builder $payments) use ($actor): void {
                        $payments
                            ->whereIn('documentable_type', $this->morphTypes->for(new Payment))
                            ->whereIn('documentable_id', $this->paymentIds($actor));
                    });
            });
        }

        return $query;
    }

    /** @return Builder<Lease> */
    private function leaseIds(User $actor): Builder
    {
        return $this->leases(Lease::query(), $actor)->select('id');
    }

    /** @return Builder<MaintenanceRequest> */
    private function maintenanceIds(User $actor): Builder
    {
        return $this->maintenance(MaintenanceRequest::query(), $actor)->select('id');
    }

    /** @return Builder<Payment> */
    private function paymentIds(User $actor): Builder
    {
        return $this->payments(Payment::query(), $actor)->select('id');
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  callable(Builder<TModel>): Builder<TModel>  $scope
     */
    private function allows(User $actor, Model $model, callable $scope): bool
    {
        if (! $this->restricts($actor)) {
            return true;
        }

        /** @var Builder<TModel> $query */
        $query = $model->newQuery()->whereKey($model->getKey());

        return $scope($query)->exists();
    }
}
