<?php

namespace App\Modules\Expenses\Support;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class ExpenseReferenceOptions
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    /** @param array<string, mixed> $defaults */
    public function selectedPortfolioId(User $actor, ?ExpenseEntry $expense, array $defaults): ?int
    {
        if ($expense) {
            return $expense->portfolio_id;
        }

        if (! $actor->hasRole('superadmin')) {
            return $actor->portfolio_id;
        }

        $requested = $this->positiveInteger($defaults['portfolio_id'] ?? null);

        if ($requested && Portfolio::query()->whereKey($requested)->where('status', 'active')->exists()) {
            return $requested;
        }

        $assetId = $this->positiveInteger($defaults['asset_id'] ?? null);

        if ($assetId) {
            return Asset::query()->whereKey($assetId)->value('portfolio_id');
        }

        $maintenanceId = $this->positiveInteger($defaults['maintenance_request_id'] ?? null);

        return $maintenanceId
            ? MaintenanceRequest::query()->whereKey($maintenanceId)->value('portfolio_id')
            : null;
    }

    /** @return array<int, array{value:int,label:string}> */
    public function portfolios(User $actor): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolios
            ->apply(Portfolio::query()->where('status', 'active')->orderBy($nameColumn), $actor, 'id')
            ->get(['id', 'name_en', 'name_ar'])
            ->map(fn (Portfolio $portfolio): array => [
                'value' => $portfolio->id,
                'label' => $this->portfolios->localized($portfolio->name_en, $portfolio->name_ar)
                    ?? trans('app.expenses.portfolio_number', ['id' => $portfolio->id]),
            ])
            ->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    public function assets(?int $portfolioId): array
    {
        if (! $portfolioId) {
            return [];
        }

        $nameColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return Asset::query()
            ->where('portfolio_id', $portfolioId)
            ->orderBy($nameColumn)
            ->get(['id', 'title_en', 'title_ar', 'code'])
            ->map(fn (Asset $asset): array => [
                'value' => $asset->id,
                'label' => trim(($this->portfolios->localized($asset->title_en, $asset->title_ar)
                    ?? trans('app.expenses.asset_number', ['id' => $asset->id])).' · '.$asset->code),
            ])
            ->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    public function maintenanceRequests(?int $portfolioId): array
    {
        if (! $portfolioId) {
            return [];
        }

        return MaintenanceRequest::query()
            ->where('portfolio_id', $portfolioId)
            ->with('asset:id,title_en,title_ar')
            ->latest('requested_at')
            ->get(['id', 'asset_id', 'title', 'status', 'requested_at'])
            ->map(fn (MaintenanceRequest $request): array => [
                'value' => $request->id,
                'label' => '#'.$request->id.' · '.$request->title.' · '.(
                    $this->portfolios->localized(
                        $request->asset?->title_en,
                        $request->asset?->title_ar,
                    ) ?? trans('app.expenses.no_asset')
                ),
            ])
            ->all();
    }

    public function currency(?int $portfolioId): string
    {
        $currency = $portfolioId
            ? Portfolio::query()->whereKey($portfolioId)->value('default_currency')
            : null;

        return strtoupper((string) ($currency ?: 'SAR'));
    }

    private function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $integer ? (int) $integer : null;
    }
}
