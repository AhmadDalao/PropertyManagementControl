<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\LeaseMoveOuts\Queries\LeaseMoveOutDirectoryQuery;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutReadiness;

final readonly class OperationsMoveOutQuery
{
    public function __construct(
        private LeaseMoveOutDirectoryQuery $directory,
        private LeaseMoveOutReadiness $readiness,
    ) {}

    /** @return array{attention:int,ready:int,items:list<array<string, mixed>>} */
    public function forUser(User $actor, DashboardPropertyContext $context): array
    {
        $base = $context
            ->leaseRecords($this->directory->base($actor))
            ->where('status', 'planned');
        $attention = clone $base;
        $ready = clone $base;
        $this->directory->applyQueue($attention, 'attention');
        $this->directory->applyQueue($ready, 'ready');

        $items = array_values(
            $this->directory
                ->listing(clone $base)
                ->orderByRaw('CASE WHEN move_out_date <= ? THEN 0 ELSE 1 END', [today()->toDateString()])
                ->orderBy('move_out_date')
                ->limit(5)
                ->get()
                ->map(fn (LeaseMoveOut $moveOut): array => $this->item($moveOut))
                ->all(),
        );

        return [
            'attention' => $attention->count(),
            'ready' => $ready->count(),
            'items' => $items,
        ];
    }

    /** @return array<string, mixed> */
    private function item(LeaseMoveOut $moveOut): array
    {
        $lease = $moveOut->lease;
        $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;
        $state = $lease ? $this->readiness->for($lease, $moveOut) : null;

        return [
            'id' => $moveOut->id,
            'lease_id' => $moveOut->lease_id,
            'code' => $lease?->code,
            'tenant' => $lease?->tenantProfile?->user?->name,
            'asset_en' => $asset?->title_en,
            'asset_ar' => $asset?->title_ar,
            'move_out_date' => $moveOut->move_out_date?->toDateString(),
            'state' => ($state['ready'] ?? false)
                ? 'ready'
                : ($moveOut->move_out_date?->isToday()
                    ? 'due_today'
                    : ($moveOut->move_out_date?->isPast() ? 'overdue' : 'scheduled')),
        ];
    }
}
