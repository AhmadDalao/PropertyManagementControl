<?php

namespace App\Modules\Expenses\Support;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use Illuminate\Validation\ValidationException;

final class ExpenseReferenceGuard
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{asset_id:int|null,maintenance_request_id:int|null}
     */
    public function withinPortfolio(User $actor, array $data, int $portfolioId): array
    {
        $assetId = $this->id($data['asset_id'] ?? null);
        $maintenanceId = $this->id($data['maintenance_request_id'] ?? null);
        $maintenance = null;

        if ($maintenanceId !== null) {
            $maintenance = MaintenanceRequest::query()
                ->lockForUpdate()
                ->whereKey($maintenanceId)
                ->where('portfolio_id', $portfolioId)
                ->first(['id', 'asset_id']);

            if (! $maintenance) {
                throw ValidationException::withMessages([
                    'maintenance_request_id' => trans('app.errors.maintenance_portfolio_mismatch'),
                ]);
            }

            abort_unless(
                $this->assignments->allowsMaintenance($actor, $maintenance),
                403,
                trans('app.errors.property_assignment_access_denied'),
            );

            if ($assetId === null && $maintenance->asset_id) {
                $assetId = $maintenance->asset_id;
            }

            if ($assetId !== null && $maintenance->asset_id && $assetId !== $maintenance->asset_id) {
                throw ValidationException::withMessages([
                    'asset_id' => trans('app.errors.expense_asset_mismatch'),
                ]);
            }
        }

        if ($assetId !== null) {
            $asset = Asset::query()
                ->lockForUpdate()
                ->whereKey($assetId)
                ->where('portfolio_id', $portfolioId)
                ->first(['id']);

            if (! $asset) {
                throw ValidationException::withMessages([
                    'asset_id' => trans('app.errors.asset_portfolio_mismatch'),
                ]);
            }

            abort_unless(
                $this->assignments->allowsAsset($actor, $asset),
                403,
                trans('app.errors.property_assignment_access_denied'),
            );
        }

        if ($this->assignments->restricts($actor) && $assetId === null) {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.manager_expense_asset_required'),
            ]);
        }

        return [
            'asset_id' => $assetId,
            'maintenance_request_id' => $maintenance?->id,
        ];
    }

    private function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id ? (int) $id : null;
    }
}
