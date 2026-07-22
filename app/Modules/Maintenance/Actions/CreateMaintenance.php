<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceReferenceGuard;
use App\Modules\Maintenance\Support\MaintenanceSchedule;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMaintenance
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceSchedule $schedule,
        private readonly MaintenanceReferenceGuard $references,
        private readonly PortfolioScope $portfolios,
        private readonly MorphTypes $morphTypes,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): MaintenanceRequest
    {
        return $actor->hasRole('tenant')
            ? $this->forTenant($actor, $data)
            : $this->forManager($actor, $data);
    }

    /** @param array<string, mixed> $data */
    private function forTenant(User $actor, array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($actor, $data): MaintenanceRequest {
            $tenant = TenantProfile::query()
                ->where('user_id', $actor->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lease = $tenant->leases()
                ->where('status', 'active')
                ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                ->where('leaseable_id', $data['asset_id'])
                ->lockForUpdate()
                ->first();

            abort_unless($lease !== null, 422, trans('app.errors.rented_asset_only'));

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
                'comment' => trans('app.maintenance.created_by_tenant'),
            ]);

            return $request;
        });
    }

    /** @param array<string, mixed> $data */
    private function forManager(User $actor, array $data): MaintenanceRequest
    {
        $this->access->ensureManager($actor);
        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', [
                    'attribute' => trans('app.fields.portfolio'),
                ]),
            ]);
        }

        $portfolioId = (int) $portfolioId;
        $this->portfolios->ensureAccess($actor, $portfolioId);

        return DB::transaction(function () use ($actor, $portfolioId, $data): MaintenanceRequest {
            $this->references->ensureBelongsToPortfolio($data, $portfolioId);
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
                'comment' => trans('app.maintenance.created_by_management'),
            ]);

            return $request;
        });
    }
}
