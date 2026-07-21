<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceSchedule;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageMaintenance
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceSchedule $schedule,
        private readonly PortfolioScope $portfolios,
        private readonly MorphTypes $morphTypes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): MaintenanceRequest
    {
        return $actor->hasRole('tenant')
            ? $this->createForTenant($actor, $data)
            : $this->createForManager($actor, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        $this->access->ensureCanAccess($actor, $request);

        if ($actor->hasRole('tenant')) {
            return DB::transaction(function () use ($actor, $request, $data): MaintenanceRequest {
                $request->updates()->create([
                    'user_id' => $actor->id,
                    'status_from' => $request->status,
                    'status_to' => $request->status,
                    'is_public_comment' => true,
                    'comment' => $data['comment'],
                ]);

                return $request->refresh();
            });
        }

        $this->ensureReferencesBelongToPortfolio($data, $request->portfolio_id);

        return DB::transaction(function () use ($actor, $request, $data): MaintenanceRequest {
            $previousStatus = $request->status;
            $previousPriority = $request->priority;
            $previousAssignee = $request->assigned_to_user_id;
            $request->update([
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'priority' => $data['priority'],
                'status' => $data['status'],
                'internal_notes' => $data['internal_notes'] ?? null,
                'due_at' => $this->schedule->nextDueAt(
                    $request,
                    $data['priority'],
                    $data['status'],
                    $previousPriority,
                ),
                'resolved_at' => $data['status'] === 'resolved' ? now() : null,
            ]);

            if (
                ! empty($data['comment'])
                || $previousStatus !== $request->status
                || $previousPriority !== $request->priority
                || (int) $previousAssignee !== (int) $request->assigned_to_user_id
            ) {
                $request->updates()->create([
                    'user_id' => $actor->id,
                    'status_from' => $previousStatus,
                    'status_to' => $request->status,
                    'is_public_comment' => (bool) ($data['is_public_comment'] ?? false),
                    'comment' => $data['comment'] ?? 'Maintenance request updated.',
                ]);
            }

            return $request->refresh();
        });
    }

    public function cancel(User $actor, MaintenanceRequest $request): bool
    {
        $this->access->ensureCanAccess($actor, $request);

        if ($actor->hasRole('tenant') && ! in_array($request->status, ['open', 'in_progress'], true)) {
            return false;
        }

        DB::transaction(function () use ($actor, $request): void {
            $previousStatus = $request->status;
            $request->update(['status' => 'cancelled']);
            $request->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $previousStatus,
                'status_to' => 'cancelled',
                'is_public_comment' => $actor->hasRole('tenant'),
                'comment' => $actor->hasRole('tenant')
                    ? 'Maintenance request cancelled by tenant.'
                    : 'Maintenance request cancelled by management.',
            ]);
        });

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createForTenant(User $actor, array $data): MaintenanceRequest
    {
        $tenant = TenantProfile::query()->where('user_id', $actor->id)->firstOrFail();
        $lease = $tenant->leases()
            ->where('status', 'active')
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->where('leaseable_id', $data['asset_id'])
            ->first();

        abort_unless($lease !== null, 422, trans('app.errors.rented_asset_only'));

        return DB::transaction(function () use ($actor, $tenant, $lease, $data): MaintenanceRequest {
            $request = MaintenanceRequest::query()->create([
                'portfolio_id' => $tenant->portfolio_id,
                'asset_id' => $data['asset_id'],
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenant->id,
                'submitted_by_user_id' => $actor->id,
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => 'open',
                'title' => $data['title'],
                'description' => $data['description'],
                'requested_at' => now(),
                'due_at' => $this->schedule->dueAtForPriority($data['priority']),
            ]);

            $request->updates()->create([
                'user_id' => $actor->id,
                'status_to' => 'open',
                'is_public_comment' => true,
                'comment' => 'Maintenance request created by tenant.',
            ]);

            return $request;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createForManager(User $actor, array $data): MaintenanceRequest
    {
        $this->access->ensureManager($actor);
        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', ['attribute' => trans('app.fields.portfolio')]),
            ]);
        }

        $portfolioId = (int) $portfolioId;
        $this->portfolios->ensureAccess($actor, $portfolioId);
        $this->ensureReferencesBelongToPortfolio($data, $portfolioId);

        return DB::transaction(function () use ($actor, $portfolioId, $data): MaintenanceRequest {
            $request = MaintenanceRequest::query()->create([
                'portfolio_id' => $portfolioId,
                'asset_id' => $data['asset_id'],
                'tenant_profile_id' => $data['tenant_profile_id'],
                'submitted_by_user_id' => $actor->id,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => $data['status'],
                'title' => $data['title'],
                'description' => $data['description'],
                'internal_notes' => $data['internal_notes'] ?? null,
                'requested_at' => now(),
                'due_at' => $this->schedule->dueAtForPriority($data['priority']),
                'resolved_at' => $data['status'] === 'resolved' ? now() : null,
            ]);

            $request->updates()->create([
                'user_id' => $actor->id,
                'status_to' => $data['status'],
                'is_public_comment' => false,
                'comment' => 'Maintenance request created by management.',
            ]);

            return $request;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureReferencesBelongToPortfolio(array $data, int $portfolioId): void
    {
        if (! empty($data['asset_id'])) {
            abort_unless(
                Asset::query()->whereKey($data['asset_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                trans('app.errors.asset_portfolio_mismatch')
            );
        }

        if (! empty($data['tenant_profile_id'])) {
            abort_unless(
                TenantProfile::query()->whereKey($data['tenant_profile_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                trans('app.errors.tenant_selection_portfolio_mismatch')
            );
        }

        if (! empty($data['assigned_to_user_id'])) {
            abort_unless(
                User::query()
                    ->whereKey($data['assigned_to_user_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->whereHas('roles', fn ($query) => $query->whereIn('name', ['owner', 'property_manager']))
                    ->exists(),
                422,
                trans('app.errors.manager_assignment_invalid')
            );
        }
    }
}
