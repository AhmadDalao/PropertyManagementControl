<?php

namespace App\Modules\Expenses\Actions;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageExpenses
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): ExpenseEntry
    {
        $this->access->ensureManager($actor);
        $portfolioId = $this->resolvePortfolioId($actor, $data['portfolio_id'] ?? null);

        return DB::transaction(function () use ($actor, $data, $portfolioId): ExpenseEntry {
            $portfolio = Portfolio::query()->lockForUpdate()->whereKey($portfolioId)->firstOrFail();

            if ($portfolio->status !== 'active') {
                throw ValidationException::withMessages([
                    'portfolio_id' => trans('app.errors.expense_portfolio_inactive'),
                ]);
            }

            $references = $this->normalizeReferences($data, $portfolioId);

            return ExpenseEntry::query()->create([
                'portfolio_id' => $portfolioId,
                'asset_id' => $references['asset_id'],
                'maintenance_request_id' => $references['maintenance_request_id'],
                'created_by_user_id' => $actor->id,
                ...$this->attributes($data, $portfolio),
            ])->load(['portfolio', 'asset', 'maintenanceRequest', 'createdBy']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, ExpenseEntry $expense, array $data): ExpenseEntry
    {
        $this->access->ensureCanManage($actor, $expense);

        return DB::transaction(function () use ($expense, $data): ExpenseEntry {
            $lockedExpense = ExpenseEntry::query()->lockForUpdate()->whereKey($expense->id)->firstOrFail();

            if ($lockedExpense->status === 'void') {
                throw ValidationException::withMessages([
                    'status' => trans('app.errors.expense_void_locked'),
                ]);
            }

            $portfolio = Portfolio::query()->whereKey($lockedExpense->portfolio_id)->firstOrFail();
            $references = $this->normalizeReferences($data, $lockedExpense->portfolio_id);

            $lockedExpense->update([
                'asset_id' => $references['asset_id'],
                'maintenance_request_id' => $references['maintenance_request_id'],
                ...$this->attributes($data, $portfolio),
            ]);

            return $lockedExpense->refresh()->load([
                'portfolio',
                'asset',
                'maintenanceRequest',
                'createdBy',
            ]);
        });
    }

    public function void(User $actor, ExpenseEntry $expense): ExpenseEntry
    {
        $this->access->ensureCanManage($actor, $expense);

        return DB::transaction(function () use ($expense): ExpenseEntry {
            $lockedExpense = ExpenseEntry::query()->lockForUpdate()->whereKey($expense->id)->firstOrFail();

            if ($lockedExpense->status !== 'void') {
                $lockedExpense->update(['status' => 'void']);
            }

            return $lockedExpense->refresh();
        });
    }

    private function resolvePortfolioId(User $actor, mixed $requestedPortfolioId): int
    {
        $portfolioId = filter_var(
            $requestedPortfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.expense_requires_portfolio'),
            ]);
        }

        $this->portfolios->ensureAccess($actor, (int) $portfolioId);

        return (int) $portfolioId;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{asset_id:int|null,maintenance_request_id:int|null}
     */
    private function normalizeReferences(array $data, int $portfolioId): array
    {
        $assetId = filled($data['asset_id'] ?? null) ? (int) $data['asset_id'] : null;
        $maintenanceId = filled($data['maintenance_request_id'] ?? null)
            ? (int) $data['maintenance_request_id']
            : null;

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
        }

        if ($maintenanceId === null) {
            return [
                'asset_id' => $assetId,
                'maintenance_request_id' => null,
            ];
        }

        $maintenance = MaintenanceRequest::query()
            ->lockForUpdate()
            ->whereKey($maintenanceId)
            ->where('portfolio_id', $portfolioId)
            ->first();

        if (! $maintenance) {
            throw ValidationException::withMessages([
                'maintenance_request_id' => trans('app.errors.maintenance_portfolio_mismatch'),
            ]);
        }

        if ($assetId === null && $maintenance->asset_id) {
            $assetId = $maintenance->asset_id;
        }

        if ($assetId !== null && $maintenance->asset_id && $assetId !== $maintenance->asset_id) {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.expense_asset_mismatch'),
            ]);
        }

        return [
            'asset_id' => $assetId,
            'maintenance_request_id' => $maintenance->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, Portfolio $portfolio): array
    {
        return [
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'incurred_on' => $data['incurred_on'],
            'amount' => $data['amount'],
            'currency' => strtoupper($portfolio->default_currency ?: 'SAR'),
            'vendor_name' => $data['vendor_name'] ?? null,
            'status' => $data['status'],
        ];
    }
}
