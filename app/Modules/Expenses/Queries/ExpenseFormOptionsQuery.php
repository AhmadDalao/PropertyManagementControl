<?php

namespace App\Modules\Expenses\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Expenses\Data\ExpenseFormData;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

final class ExpenseFormOptionsQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolioScope,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function get(User $actor, ?ExpenseEntry $expense = null, array $defaults = []): ExpenseFormData
    {
        $portfolios = $this->activePortfolios($actor);
        $portfolioId = $this->portfolioId($actor, $expense, $portfolios, $defaults);

        return new ExpenseFormData(
            actor: $actor,
            expense: $expense,
            defaults: $defaults,
            portfolioId: $portfolioId,
            currency: $expense?->currency ?: $this->currency($portfolioId),
            portfolios: $portfolios,
            assets: $this->assets($portfolioId),
            maintenanceRequests: $this->maintenanceRequests($portfolioId),
        );
    }

    /** @return array<int, array{value:int,label:string}> */
    private function activePortfolios(User $actor): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolioScope
            ->apply(Portfolio::query(), $actor, 'id')
            ->where('status', 'active')
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar'])
            ->map(fn (Portfolio $portfolio): array => [
                'value' => $portfolio->id,
                'label' => $this->resources->localized($portfolio->name_en, $portfolio->name_ar)
                    ?? trans('app.expenses.portfolio_number', ['id' => $portfolio->id]),
            ])->all();
    }

    /**
     * @param  array<int, array{value:int,label:string}>  $portfolios
     * @param  array<string, mixed>  $defaults
     */
    private function portfolioId(User $actor, ?ExpenseEntry $expense, array $portfolios, array $defaults): ?int
    {
        if ($expense) {
            return $expense->portfolio_id;
        }

        $ids = collect($portfolios)->pluck('value');
        $requested = $this->id($defaults['portfolio_id'] ?? null);

        if ($requested && $ids->contains($requested)) {
            return $requested;
        }

        if (! $actor->hasRole('superadmin')) {
            return $actor->portfolio_id && $ids->contains($actor->portfolio_id)
                ? $actor->portfolio_id
                : null;
        }

        $assetId = $this->id($defaults['asset_id'] ?? null);
        $assetPortfolio = $assetId
            ? Asset::query()->whereKey($assetId)->whereIn('portfolio_id', $ids->all())->value('portfolio_id')
            : null;

        if ($assetPortfolio) {
            return (int) $assetPortfolio;
        }

        $maintenanceId = $this->id($defaults['maintenance_request_id'] ?? null);
        $maintenancePortfolio = $maintenanceId
            ? MaintenanceRequest::query()->whereKey($maintenanceId)->whereIn('portfolio_id', $ids->all())->value('portfolio_id')
            : null;

        return $maintenancePortfolio ? (int) $maintenancePortfolio : null;
    }

    /** @return array<int, array{value:int,label:string}> */
    private function assets(?int $portfolioId): array
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
                'label' => trim(($this->resources->localized($asset->title_en, $asset->title_ar)
                    ?? trans('app.expenses.asset_number', ['id' => $asset->id])).' · '.$asset->code),
            ])->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    private function maintenanceRequests(?int $portfolioId): array
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
                'label' => implode(' · ', array_filter([
                    '#'.$request->id,
                    $request->title,
                    trans("app.status.{$request->status}"),
                    $this->resources->localized($request->asset?->title_en, $request->asset?->title_ar)
                        ?? trans('app.expenses.no_asset'),
                ])),
            ])->all();
    }

    private function currency(?int $portfolioId): string
    {
        $currency = $portfolioId
            ? Portfolio::query()->whereKey($portfolioId)->value('default_currency')
            : null;

        return strtoupper((string) ($currency ?: 'SAR'));
    }

    private function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id ? (int) $id : null;
    }
}
