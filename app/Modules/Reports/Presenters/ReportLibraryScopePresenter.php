<?php

namespace App\Modules\Reports\Presenters;

use App\Models\User;
use Carbon\CarbonImmutable;

final class ReportLibraryScopePresenter
{
    /**
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @param  array<int, array{id:int,name:string}>  $portfolioOptions
     * @param  array<int, array{id:int,portfolio_id:int,name:string}>  $propertyOptions
     * @return array{
     *     period:array<int, array{label:string,value:string}>,
     *     current:array<int, array{label:string,value:string}>,
     *     audit:array<int, array{label:string,value:string}>
     * }
     */
    public function present(
        User $actor,
        array $filters,
        array $portfolioOptions,
        array $propertyOptions,
    ): array {
        $propertyPortfolioId = $this->propertyPortfolioId(
            $filters['property_id'],
            $propertyOptions,
        );
        $portfolio = $this->portfolio(
            $actor,
            $filters['portfolio_id'] ?? $propertyPortfolioId,
            $portfolioOptions,
        );
        $property = $this->property($filters['property_id'], $propertyOptions);
        $period = $this->period($filters);

        return [
            'period' => [
                $this->item('period', $period),
                $this->item('portfolio', $portfolio),
                $this->item('property', $property),
            ],
            'current' => [
                $this->item('current', $this->date(now()->toDateString())),
                $this->item('portfolio', $portfolio),
                $this->item('property', $property),
            ],
            'audit' => [
                $this->item('period', $period),
                $this->item('portfolio', $portfolio),
            ],
        ];
    }

    /** @param array{period:string,date_from:string,date_to:string} $filters */
    private function period(array $filters): string
    {
        return trans('app.reports.scope_period_detail', [
            'period' => trans('app.reports.period_'.$filters['period']),
            'from' => $this->date($filters['date_from']),
            'to' => $this->date($filters['date_to']),
        ]);
    }

    /** @param array<int, array{id:int,name:string}> $options */
    private function portfolio(User $actor, ?int $selectedId, array $options): string
    {
        $effectiveId = $selectedId
            ?? ($actor->hasRole('superadmin') ? null : $actor->portfolio_id);

        return $this->option($effectiveId, $options)
            ?? trans('app.reports.all_portfolios');
    }

    /** @param array<int, array{id:int,portfolio_id:int,name:string}> $options */
    private function property(?int $selectedId, array $options): string
    {
        return $this->option($selectedId, $options)
            ?? trans('app.reports.all_properties');
    }

    /** @param array<int, array{id:int,portfolio_id:int,name:string}> $options */
    private function propertyPortfolioId(?int $selectedId, array $options): ?int
    {
        if ($selectedId === null) {
            return null;
        }

        foreach ($options as $option) {
            if ($option['id'] === $selectedId) {
                return $option['portfolio_id'];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{id:int,name:string}>|array<int, array{id:int,portfolio_id:int,name:string}>  $options
     */
    private function option(?int $selectedId, array $options): ?string
    {
        if ($selectedId === null) {
            return null;
        }

        foreach ($options as $option) {
            if ($option['id'] === $selectedId) {
                return $option['name'];
            }
        }

        return null;
    }

    /** @return array{label:string,value:string} */
    private function item(string $key, string $value): array
    {
        return [
            'label' => trans('app.reports.scope_'.$key),
            'value' => $value,
        ];
    }

    private function date(string $date): string
    {
        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        if (! $parsed instanceof CarbonImmutable) {
            return $date;
        }

        $localized = $parsed->locale(app()->getLocale());

        return $localized instanceof CarbonImmutable
            ? $localized->translatedFormat('j M Y')
            : $date;
    }
}
