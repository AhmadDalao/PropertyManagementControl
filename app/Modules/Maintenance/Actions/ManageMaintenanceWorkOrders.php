<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceReferenceGuard;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderAccess;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageMaintenanceWorkOrders
{
    public function __construct(
        private readonly MaintenanceWorkOrderAccess $access,
        private readonly MaintenanceReferenceGuard $references,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, MaintenanceRequest $request, array $data): MaintenanceWorkOrder
    {
        $this->access->ensureCanManageRequest($actor, $request);

        return DB::transaction(function () use ($actor, $request, $data): MaintenanceWorkOrder {
            $lockedRequest = MaintenanceRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->access->ensureCanManageRequest($actor, $lockedRequest);
            $this->ensureNoActiveWorkOrder($lockedRequest);
            $vendor = $this->vendor($lockedRequest, $data['vendor_id'], true);
            $this->references->ensureBelongsToPortfolio(
                $actor,
                $data,
                $lockedRequest->portfolio_id,
                $lockedRequest->asset_id,
            );
            $this->ensureStateRequirements($data);
            $portfolio = Portfolio::query()->findOrFail($lockedRequest->portfolio_id);

            $workOrder = MaintenanceWorkOrder::query()->create([
                'portfolio_id' => $lockedRequest->portfolio_id,
                'maintenance_request_id' => $lockedRequest->id,
                'vendor_id' => $vendor->id,
                'created_by_user_id' => $actor->id,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'reference_code' => $this->referenceCode(),
                'vendor_name' => $vendor->name,
                'vendor_phone' => $vendor->phone,
                'status' => $data['status'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'estimated_amount' => $data['estimated_amount'] ?? null,
                'currency' => strtoupper($portfolio->default_currency ?: 'SAR'),
                'scope' => trim((string) $data['scope']),
                'tenant_access_required' => (bool) ($data['tenant_access_required'] ?? false),
            ]);

            $this->startRequestWhenScheduled($actor, $lockedRequest, $workOrder);

            return $workOrder->load(['maintenanceRequest.asset', 'vendor', 'assignedTo', 'createdBy']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, MaintenanceWorkOrder $workOrder, array $data): MaintenanceWorkOrder
    {
        $this->access->ensureCanManage($actor, $workOrder);

        return DB::transaction(function () use ($actor, $workOrder, $data): MaintenanceWorkOrder {
            $locked = MaintenanceWorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);
            $locked->loadMissing('maintenanceRequest');
            $this->access->ensureCanManage($actor, $locked);
            $request = $locked->maintenanceRequest;

            if ($request === null) {
                abort(404);
            }

            $vendor = $this->vendor(
                $request,
                $data['vendor_id'],
                (int) $data['vendor_id'] !== (int) $locked->vendor_id,
            );
            $this->references->ensureBelongsToPortfolio(
                $actor,
                $data,
                $request->portfolio_id,
                $request->asset_id,
            );
            $this->ensureTransition($locked->status, $data['status']);
            $this->ensureStateRequirements($data);
            $completing = $data['status'] === 'completed';

            $locked->update([
                'vendor_id' => $vendor->id,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'vendor_name' => $vendor->name,
                'vendor_phone' => $vendor->phone,
                'status' => $data['status'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'completed_at' => $completing ? ($locked->completed_at ?: now()) : null,
                'estimated_amount' => $data['estimated_amount'] ?? null,
                'final_amount' => $data['final_amount'] ?? null,
                'scope' => trim((string) $data['scope']),
                'completion_notes' => $this->optional($data['completion_notes'] ?? null),
                'tenant_access_required' => (bool) ($data['tenant_access_required'] ?? false),
            ]);

            $this->startRequestWhenScheduled($actor, $request, $locked);

            return $locked->refresh()->load([
                'maintenanceRequest.asset',
                'vendor',
                'assignedTo',
                'createdBy',
            ]);
        }, attempts: 3);
    }

    private function vendor(
        MaintenanceRequest $request,
        mixed $vendorId,
        bool $requireActive,
    ): MaintenanceVendor {
        $vendor = MaintenanceVendor::query()
            ->whereKey($vendorId)
            ->where('portfolio_id', $request->portfolio_id)
            ->when($requireActive, fn ($query) => $query->where('status', 'active'))
            ->first();

        if (! $vendor) {
            throw ValidationException::withMessages([
                'vendor_id' => trans('app.errors.maintenance_vendor_invalid'),
            ]);
        }

        return $vendor;
    }

    private function ensureTransition(string $from, string $to): void
    {
        if (! in_array($to, MaintenanceWorkOrderOptions::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => trans('app.errors.work_order_transition_invalid'),
            ]);
        }
    }

    private function ensureNoActiveWorkOrder(MaintenanceRequest $request): void
    {
        if ($request->workOrders()->whereIn('status', [
            'draft',
            'scheduled',
            'in_progress',
        ])->exists()) {
            throw ValidationException::withMessages([
                'status' => trans('app.errors.work_order_active_exists'),
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureStateRequirements(array $data): void
    {
        $errors = [];

        if ($data['status'] === 'scheduled' && empty($data['scheduled_at'])) {
            $errors['scheduled_at'] = trans('app.errors.work_order_schedule_required');
        }

        if ($data['status'] === 'completed') {
            if (! array_key_exists('final_amount', $data) || $data['final_amount'] === null || $data['final_amount'] === '') {
                $errors['final_amount'] = trans('app.errors.work_order_final_amount_required');
            }

            if (trim((string) ($data['completion_notes'] ?? '')) === '') {
                $errors['completion_notes'] = trans('app.errors.work_order_completion_notes_required');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function startRequestWhenScheduled(
        User $actor,
        MaintenanceRequest $request,
        MaintenanceWorkOrder $workOrder,
    ): void {
        if (
            ! in_array($workOrder->status, ['scheduled', 'in_progress', 'completed'], true)
            || ! in_array($request->status, ['open', 'in_progress'], true)
        ) {
            return;
        }

        $previous = $request->status;
        $request->update([
            'status' => 'in_progress',
            'assigned_to_user_id' => $workOrder->assigned_to_user_id
                ?: $request->assigned_to_user_id,
        ]);

        if ($previous !== 'in_progress') {
            $request->updates()->create([
                'user_id' => $actor->id,
                'status_from' => $previous,
                'status_to' => 'in_progress',
                'is_public_comment' => true,
                'comment' => trans('app.maintenance.work_order_scheduled_update', [
                    'reference' => $workOrder->reference_code,
                ]),
            ]);
        }
    }

    private function referenceCode(): string
    {
        do {
            $reference = 'WO-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (MaintenanceWorkOrder::query()->where('reference_code', $reference)->exists());

        return $reference;
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
